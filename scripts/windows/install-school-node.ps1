param(
    [string] $TaskName = 'Portal Escolar - Nodo Biometrico',
    [int] $Port = 8000,
    [switch] $RunAtStartup,
    [switch] $EnableServe,
    [switch] $ValidationOnly
)

$ErrorActionPreference = 'Stop'
$runnerPath = (Resolve-Path (Join-Path $PSScriptRoot 'run-school-node.ps1')).Path
$projectPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$powerShellPath = (Get-Command powershell.exe -ErrorAction Stop).Source
$bundledPhpCommand = Join-Path $projectPath 'runtime\php\php.exe'
$bundledPhpIni = Join-Path $projectPath 'runtime\php\php.ini'
$bundledPhpExtensions = Join-Path $projectPath 'runtime\php\ext'
$virtualEnvironmentPath = Join-Path $projectPath 'runtime\python-venv'
$virtualEnvironmentPython = Join-Path $virtualEnvironmentPath 'Scripts\python.exe'
$requirementsPath = Join-Path $projectPath 'scripts\requirements-biometric.txt'
$wheelhousePath = Join-Path $projectPath 'scripts\wheels'

if (-not (Test-Path -LiteralPath $bundledPhpCommand)) {
    throw 'El paquete está incompleto: falta runtime\php\php.exe.'
}

if ($null -eq (Get-Command 'py' -ErrorAction SilentlyContinue)) {
    throw 'No se encontró el iniciador de Python (py.exe) en PATH. Reinstale Python 3.14 activando Python Launcher.'
}

$systemPython = (& py -3.14 -c 'import sys; print(sys.executable)' 2>$null | Select-Object -Last 1)
if ($LASTEXITCODE -ne 0) {
    throw 'Este paquete requiere Python 3.14 de 64 bits.'
}
$systemPython = $systemPython.Trim()
if (-not (Test-Path -LiteralPath $systemPython)) {
    throw 'Python 3.14 está registrado, pero no se encontró su ejecutable. Repare la instalación de Python.'
}

& $systemPython -c 'import sys; raise SystemExit(0 if sys.maxsize > 2**32 else 1)'
if ($LASTEXITCODE -ne 0) {
    throw 'Este paquete requiere Python 3.14 de 64 bits; se detectó una instalación de 32 bits.'
}

if (-not $ValidationOnly -and $null -eq (Get-Command tailscale -ErrorAction SilentlyContinue)) {
    throw 'No se encontró Tailscale en PATH. Instálelo e inicie sesión antes de registrar el nodo.'
}

if (-not (Test-Path -LiteralPath (Join-Path $projectPath '.env'))) {
    throw 'Falta .env. Configure primero PostgreSQL, APP_KEY y BIOMETRIC_API_TOKEN.'
}

$env:PHPRC = $bundledPhpIni
$env:PHP_INI_SCAN_DIR = (Resolve-Path (Join-Path $projectPath 'scripts\php-conf.d')).Path

$phpModules = & $bundledPhpCommand -c $bundledPhpIni -d "extension_dir=$bundledPhpExtensions" -m
if ($LASTEXITCODE -ne 0 -or $phpModules -notcontains 'pdo_pgsql' -or $phpModules -notcontains 'pgsql') {
    throw 'El PHP portátil no pudo cargar pdo_pgsql y pgsql.'
}

if (Test-Path -LiteralPath $virtualEnvironmentPython) {
    & $virtualEnvironmentPython -c 'import sys' 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host 'El entorno virtual existente pertenece a otra instalación. Reconstruyéndolo...'
        Remove-Item -LiteralPath $virtualEnvironmentPath -Recurse -Force
    }
}

if (-not (Test-Path -LiteralPath $virtualEnvironmentPython)) {
    Write-Host 'Creando el entorno virtual de Python...'
    & $systemPython -m venv $virtualEnvironmentPath
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo crear el entorno virtual de Python.'
    }
}

$pipArguments = @('-m', 'pip', 'install', '--disable-pip-version-check')
if (Test-Path -LiteralPath $wheelhousePath) {
    $pipArguments += @('--no-index', '--find-links', $wheelhousePath)
}
$pipArguments += @('-r', $requirementsPath)
& $virtualEnvironmentPython @pipArguments
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudieron instalar las dependencias biométricas en el entorno virtual.'
}

& $virtualEnvironmentPython -c 'import dotenv, requests, zk'
if ($LASTEXITCODE -ne 0) {
    throw 'El entorno virtual no puede importar dotenv, requests y zk.'
}

& $bundledPhpCommand -c $bundledPhpIni -d "extension_dir=$bundledPhpExtensions" artisan optimize:clear
if ($LASTEXITCODE -ne 0) {
    throw 'Laravel no pudo limpiar su configuración.'
}

& $bundledPhpCommand -c $bundledPhpIni -d "extension_dir=$bundledPhpExtensions" artisan migrate:status --no-interaction
if ($LASTEXITCODE -ne 0) {
    throw 'Laravel no pudo conectarse con PostgreSQL en TrueNAS.'
}

Write-Host 'PHP, Python, Laravel y PostgreSQL: OK'

if ($ValidationOnly) {
    Write-Host 'Validación terminada; no se registró ninguna tarea de Windows.'
    exit 0
}

$existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($null -ne $existingTask) {
    Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
}

$existingListener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($null -ne $existingListener) {
    $listenerProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $($existingListener.OwningProcess)"
    $isBundledPhp = $listenerProcess.ExecutablePath -eq $bundledPhpCommand
    $isPortalPhpServer = $listenerProcess.Name -eq 'php.exe' -and $listenerProcess.CommandLine -like '*scripts/server.php*'

    if (-not $isBundledPhp -and -not $isPortalPhpServer) {
        throw "El puerto $Port está ocupado por otro programa (PID $($existingListener.OwningProcess))."
    }

    Stop-Process -Id $existingListener.OwningProcess -Force
}

& tailscale status | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw 'Tailscale no está conectado. Inicie sesión y vuelva a ejecutar el instalador.'
}

$runnerArguments = "-NoProfile -ExecutionPolicy Bypass -File `"$runnerPath`" -Port $Port"
if ($EnableServe) {
    $runnerArguments += ' -EnableServe'
}

$action = New-ScheduledTaskAction -Execute $powerShellPath -Argument $runnerArguments -WorkingDirectory $projectPath
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit (New-TimeSpan -Days 3650)

if ($RunAtStartup) {
    $credential = Get-Credential -UserName "$env:USERDOMAIN\$env:USERNAME" -Message 'Credenciales de Windows para ejecutar el nodo antes de iniciar sesión'
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($credential.Password)
    try {
        $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
        Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger (New-ScheduledTaskTrigger -AtStartup) -Settings $settings -User $credential.UserName -Password $plainPassword -RunLevel Highest -Force | Out-Null
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
        $plainPassword = $null
    }
}
else {
    $principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Highest
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger (New-ScheduledTaskTrigger -AtLogOn) -Settings $settings -Principal $principal -Force | Out-Null
}

Start-ScheduledTask -TaskName $TaskName
Write-Host "Nodo instalado y arrancado. Tarea: $TaskName"
Write-Host "Panel local: http://127.0.0.1:$Port"
if ($EnableServe) {
    & tailscale serve status
}

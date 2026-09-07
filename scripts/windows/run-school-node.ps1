param(
    [int] $Port = 2080,
    [switch] $EnableServe,
    [switch] $SkipQueue
)

$ErrorActionPreference = 'Stop'
$projectPath = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$logPath = Join-Path $projectPath 'storage\logs'
$bundledPhpCommand = Join-Path $projectPath 'runtime\php\php.exe'
$bundledPhpIni = Join-Path $projectPath 'runtime\php\php.ini'
$phpCommand = if (Test-Path -LiteralPath $bundledPhpCommand) {
    $env:PHPRC = $bundledPhpIni
    $env:PHP_INI_SCAN_DIR = (Resolve-Path (Join-Path $projectPath 'scripts\php-conf.d')).Path
    $bundledPhpCommand
}
else {
    (Get-Command php -ErrorAction Stop).Source
}
$processes = @{}

New-Item -ItemType Directory -Path $logPath -Force | Out-Null
Set-Location -LiteralPath $projectPath

if (-not (Test-Path -LiteralPath (Join-Path $projectPath '.env'))) {
    throw 'Falta el archivo .env. Configure la base de datos y los tokens antes de instalar el nodo.'
}

function Start-NodeProcess {
    param(
        [string] $Name,
        [string] $Executable,
        [string[]] $Arguments
    )

    $stdoutPath = Join-Path $logPath "$Name.out.log"
    $stderrPath = Join-Path $logPath "$Name.error.log"
    $processes[$Name] = Start-Process -FilePath $Executable -ArgumentList $Arguments -WorkingDirectory $projectPath -WindowStyle Hidden -PassThru -RedirectStandardOutput $stdoutPath -RedirectStandardError $stderrPath
}

function Ensure-NodeProcesses {
    $definitions = @(
        @{ Name = 'web'; Executable = $phpCommand; Arguments = @('-S', "127.0.0.1:$Port", '-t', 'public', 'scripts/server.php') }
    )

    if (-not $SkipQueue) {
        $definitions += @{ Name = 'queue'; Executable = $phpCommand; Arguments = @('artisan', 'queue:work', '--sleep=2', '--tries=3', '--timeout=90') }
    }

    foreach ($definition in $definitions) {
        $currentProcess = $processes[$definition.Name]
        if ($null -eq $currentProcess -or $currentProcess.HasExited) {
            Start-NodeProcess -Name $definition.Name -Executable $definition.Executable -Arguments $definition.Arguments
        }
    }
}

try {
    Ensure-NodeProcesses

    if ($EnableServe) {
        $tailscaleCommand = (Get-Command tailscale -ErrorAction Stop).Source
        & $tailscaleCommand serve --bg "http://127.0.0.1:$Port"
    }

    while ($true) {
        Ensure-NodeProcesses
        Start-Sleep -Seconds 5
    }
}
finally {
    foreach ($nodeProcess in $processes.Values) {
        if ($null -ne $nodeProcess -and -not $nodeProcess.HasExited) {
            Stop-Process -Id $nodeProcess.Id
        }
    }
}

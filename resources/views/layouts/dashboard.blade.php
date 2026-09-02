<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>{{ $roleLabel }} | Portal escolar</title>
    <script>
        (() => {
            const theme = localStorage.getItem('school-panel-theme');
            const useDark = theme === 'dark' || (! theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', useDark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-stone-50 font-sans text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    @php
        $isSuperadmin = ($usuario['role'] ?? null) === 'superadmin';
        $activeSchool = $schools->firstWhere('code', $usuario['institution_code'] ?? null) ?? $schools->first();
        $statusMeta = [
            'online' => ['En línea', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300', 'bg-emerald-500'],
            'delayed' => ['Con retraso', 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300', 'bg-amber-500'],
            'offline' => ['Desconectado', 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300', 'bg-rose-500'],
            'never_connected' => ['Sin conectar', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300', 'bg-slate-400'],
            'inactive' => ['Inactivo', 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400', 'bg-slate-400'],
        ];
        $fingerNames = ['Pulgar derecho', 'Índice derecho', 'Medio derecho', 'Anular derecho', 'Meñique derecho', 'Pulgar izquierdo', 'Índice izquierdo', 'Medio izquierdo', 'Anular izquierdo', 'Meñique izquierdo'];
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)]">
        <aside class="hidden border-r border-teal-900/70 bg-teal-950 text-teal-50 lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
            <div class="border-b border-white/10 px-6 py-6">
                <a class="flex items-center gap-3" href="{{ route('dashboard.admin') }}">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-300 text-teal-950 shadow-lg shadow-amber-950/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 4l9 6.5M5 9.5V20h14V9.5M9 20v-6h6v6M4 20h16"/></svg>
                    </span>
                    <span><strong class="block text-base tracking-tight">Portal escolar</strong><span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[.22em] text-teal-300">Asistencia inteligente</span></span>
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6 text-sm">
                @foreach ([
                    ['overview', 'Resumen', 'M4 13h6V4H4v9zm10 7h6V11h-6v9zM4 20h6v-3H4v3zm10-13h6V4h-6v3z'],
                    ['devices', 'Dispositivos', 'M9.75 17L9 20l-1 .75h8L15 20l-.75-3M4 5h16v12H4V5z'],
                    ['enrollment', 'Registro de huellas', 'M12 11a3 3 0 100-6 3 3 0 000 6zm-6 9v-2a6 6 0 0112 0v2M5 12a8 8 0 0114-5M4 16a10 10 0 0116-7'],
                    ['students', 'Estudiantes', 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m14-11a4 4 0 100-8 4 4 0 000 8zm3 11v-2a4 4 0 00-3-3.87'],
                    ['courses', 'Cursos y áreas', 'M3 5h18v14H3V5zm4 0v14m10-14v14M7 10h10'],
                    ['teachers', 'Docentes', 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m8-11a4 4 0 100-8 4 4 0 000 8zm5-4h3m-1.5-1.5v3'],
                    ['settings', 'Configuración', 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zm7.4-3.5a7.8 7.8 0 00-.1-1l2-1.6-2-3.4-2.5 1a8.3 8.3 0 00-1.7-1L14.7 3h-4l-.4 3a8.3 8.3 0 00-1.7 1L6.1 6l-2 3.4 2 1.6a7.8 7.8 0 000 2L4.1 14.6l2 3.4 2.5-1a8.3 8.3 0 001.7 1l.4 3h4l.4-3a8.3 8.3 0 001.7-1l2.5 1 2-3.4-2-1.6a7.8 7.8 0 00.1-1z'],
                    ['attendance', 'Actividad en vivo', 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['reports', 'Reportes', 'M4 19V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2zm9-16v6h6M8 14h8M8 17h5'],
                ] as [$anchor, $label, $path])
                    <a class="group flex items-center gap-3 rounded-xl px-3 py-3 font-semibold text-teal-100/70 transition hover:bg-white/10 hover:text-white" href="#{{ $anchor }}">
                        <svg class="h-5 w-5 text-teal-300 transition group-hover:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/></svg>{{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="p-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center justify-between"><span class="text-xs font-bold uppercase tracking-wider text-teal-200">Red escolar</span><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400"></span></div>
                    <p class="mt-3 text-sm font-semibold text-white">{{ $deviceSummary['online'] }} de {{ $deviceSummary['total'] }} lectores activos</p>
                    <p class="mt-1 text-xs leading-5 text-teal-100/55">El agente SDK y Laravel se ejecutan juntos.</p>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-stone-200/80 bg-stone-50/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/85">
                <div class="flex h-20 items-center justify-between gap-4 px-5 sm:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-sm font-black text-white shadow-lg shadow-teal-800/20">{{ strtoupper(substr($activeSchool?->short_name ?? $activeSchool?->name ?? 'CE', 0, 2)) }}</span>
                        <div class="min-w-0"><p class="truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ $isSuperadmin ? 'Red de centros educativos' : ($activeSchool?->name ?? 'Centro educativo') }}</p><p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $isSuperadmin ? $schools->count().' escuelas supervisadas' : ($activeSchool?->code ?? 'Institución') }} · {{ $roleLabel }}</p></div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button data-theme-toggle class="flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-slate-600 transition hover:border-teal-300 hover:text-teal-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-teal-700" type="button" aria-label="Cambiar tema">
                            <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.8A8.5 8.5 0 1111.2 3 6.7 6.7 0 0021 12.8z"/></svg>
                            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36l-1.42 1.42M7.05 16.95l-1.41 1.41m12.72 0l-1.42-1.42M7.05 7.05L5.64 5.64M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </button>
                        <div class="hidden text-right sm:block"><p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $usuario['username'] ?? $roleLabel }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ now()->format('d/m/Y · H:i') }}</p></div>
                        <form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" type="submit">Salir</button></form>
                    </div>
                </div>
                <nav class="no-scrollbar flex gap-1 overflow-x-auto border-t border-stone-200/70 px-4 py-2 text-xs font-bold lg:hidden dark:border-slate-800">
                    @foreach ([['overview', 'Resumen'], ['devices', 'Lectores'], ['enrollment', 'Huellas'], ['students', 'Estudiantes'], ['attendance', 'En vivo']] as [$anchor, $label])
                        <a class="whitespace-nowrap rounded-lg px-3 py-2 text-slate-500 hover:bg-teal-50 hover:text-teal-700 dark:text-slate-400 dark:hover:bg-teal-950" href="#{{ $anchor }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </header>

            <main class="mx-auto max-w-[1500px] space-y-8 px-5 py-7 sm:px-8 sm:py-9">
                @if (session('success'))
                    <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">✓</span>{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200">{{ $errors->first() }}</div>
                @endif

                <section id="overview" class="scroll-mt-32 overflow-hidden rounded-3xl bg-teal-900 text-white shadow-xl shadow-teal-950/10 dark:bg-teal-950">
                    <div class="relative grid gap-8 px-6 py-8 sm:px-9 lg:grid-cols-[1.3fr_.7fr] lg:items-end lg:px-10 lg:py-10">
                        <div class="absolute -right-20 -top-32 h-80 w-80 rounded-full border-[45px] border-white/5"></div>
                        <div class="relative"><span class="inline-flex items-center gap-2 rounded-full bg-amber-300 px-3 py-1.5 text-[11px] font-black uppercase tracking-[.16em] text-teal-950"><span class="h-2 w-2 rounded-full bg-teal-700"></span>Jornada escolar en curso</span><h1 class="mt-5 max-w-2xl text-3xl font-black leading-tight tracking-tight sm:text-4xl">Una vista clara de la asistencia y de cada lector de la escuela.</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-teal-100/75">Monitorea entradas, administra dispositivos y registra huellas sin interrumpir la captura biométrica.</p></div>
                        <div class="relative grid grid-cols-2 gap-3"><div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur"><p class="text-xs font-bold uppercase tracking-wider text-teal-200">Asistencia hoy</p><p class="mt-2 text-3xl font-black">{{ $resumen['porcentaje_hoy'] }}%</p></div><div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur"><p class="text-xs font-bold uppercase tracking-wider text-teal-200">Lectores en línea</p><p class="mt-2 text-3xl font-black">{{ $deviceSummary['online'] }}/{{ $deviceSummary['total'] }}</p></div></div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['Estudiantes', $resumen['total_estudiantes'], 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m14-11a4 4 0 100-8 4 4 0 000 8z', 'bg-teal-100 text-teal-700 dark:bg-teal-400/15 dark:text-teal-300'],
                        ['Entradas hoy', $resumen['presentes_hoy'], 'M5 12l4 4L19 6', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300'],
                        ['Ausentes', $resumen['ausentes_hoy'], 'M6 18L18 6M6 6l12 12', 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300'],
                        ['Salidas', $resumen['salidas_hoy'], 'M13 5l7 7-7 7M4 12h16', 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300'],
                    ] as [$label, $value, $icon, $iconColor])
                        <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><div><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</p><p class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ number_format($value) }}</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $iconColor }}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg></span></div><p class="mt-4 text-xs font-medium text-slate-400">Actualizado {{ now()->format('H:i') }}</p></article>
                    @endforeach
                </section>

                <section id="devices" class="scroll-mt-32 space-y-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Centro de operaciones</p><h2 class="mt-1 text-2xl font-black tracking-tight">Red global de lectores</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Una vista central, como la consola de un ISP, para supervisar cada escuela y lector.</p></div><div class="flex gap-2"><span class="rounded-full bg-emerald-100 px-3 py-2 text-xs font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ $deviceSummary['online'] }} en línea</span><span class="rounded-full bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700 dark:bg-rose-400/15 dark:text-rose-300">{{ $deviceSummary['offline'] }} sin conexión</span></div></div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($schoolNetwork as $network)
                            <article class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-3"><div><p class="font-extrabold text-slate-900 dark:text-white">{{ $network['school']->short_name ?: $network['school']->name }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $network['school']->code }}</p></div><span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $network['alerts'] ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300' }}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20h16M6 20V8l6-4 6 4v12M9 12h2m2 0h2M9 16h2m2 0h2"/></svg></span></div>
                                <div class="mt-4 flex items-end justify-between"><div><span class="text-3xl font-black">{{ $network['online'] }}</span><span class="text-sm text-slate-400"> / {{ $network['total'] }} activos</span></div>@if ($network['alerts'])<span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ $network['alerts'] }} alerta{{ $network['alerts'] === 1 ? '' : 's' }}</span>@else<span class="text-xs font-bold text-emerald-600 dark:text-emerald-300">Todo estable</span>@endif</div>
                            </article>
                        @endforeach
                    </div>

                    <div class="grid min-w-0 gap-4 xl:grid-cols-3">
                        @forelse ($devices as $device)
                            @php([$statusLabel, $statusClass, $statusDot] = $statusMeta[$device->connectionStatus()])
                            <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-400/10 dark:text-teal-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5h14v12H5V5zm4 16h6m-3-4v4M8 9h8m-8 4h5"/></svg></span><div class="min-w-0"><h3 class="truncate font-extrabold text-slate-900 dark:text-white">{{ $device->name }}</h3><p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $device->school?->name }} · {{ $device->location ?: 'Sin ubicación' }}</p></div></div><span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $statusClass }}"><span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>{{ $statusLabel }}</span></div>
                                <dl class="mt-5 grid min-w-0 grid-cols-2 gap-x-4 gap-y-3 text-xs"><div class="min-w-0"><dt class="text-slate-400">Conexión</dt><dd class="mt-1 font-bold uppercase text-slate-700 dark:text-slate-200">{{ $device->connection_mode }}</dd></div><div class="min-w-0"><dt class="text-slate-400">IP detectada</dt><dd class="mt-1 font-mono font-bold text-slate-700 dark:text-slate-200">{{ $device->ip_address ?: 'Pendiente' }}</dd></div><div class="min-w-0"><dt class="text-slate-400">MAC</dt><dd class="mt-1 break-all font-mono font-bold text-slate-700 dark:text-slate-200">{{ $device->mac_address ?: 'No aplica' }}</dd></div><div class="min-w-0"><dt class="text-slate-400">Modelo</dt><dd class="mt-1 truncate font-bold text-slate-700 dark:text-slate-200">{{ $device->model ?: 'Por consultar' }}</dd></div><div class="min-w-0"><dt class="text-slate-400">Número de serie</dt><dd class="mt-1 truncate font-bold text-slate-700 dark:text-slate-200">{{ $device->serial_number ?: 'Por consultar' }}</dd></div><div class="min-w-0"><dt class="text-slate-400">Usuarios / huellas</dt><dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $device->user_count }} / {{ $device->fingerprint_count }}</dd></div><div class="min-w-0"><dt class="text-slate-400">Último contacto</dt><dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $device->last_seen_at?->diffForHumans() ?? 'Nunca' }}</dd></div><div class="col-span-2 min-w-0"><dt class="text-slate-400">Último ponche sincronizado</dt><dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $device->attendances_max_fecha_hora ? \Carbon\Carbon::parse($device->attendances_max_fecha_hora)->diffForHumans() : 'Ninguno' }}</dd></div></dl>
                                @if ($device->last_error)<p class="mt-4 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">{{ $device->last_error }}</p>@endif
                                <div class="mt-5 flex flex-wrap gap-2"><form action="{{ route('devices.inspect', $device) }}" method="POST">@csrf<button class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white hover:bg-teal-600" type="submit">Consultar lector</button></form><form action="{{ route('devices.sync-time', $device) }}" method="POST">@csrf<button class="rounded-lg border border-stone-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-teal-300 hover:text-teal-700 dark:border-slate-700 dark:text-slate-300" type="submit">Sincronizar hora</button></form><details class="w-full pt-1"><summary class="cursor-pointer text-xs font-bold text-teal-700 dark:text-teal-300">Editar configuración</summary><form class="mt-3 grid gap-3 rounded-xl bg-stone-50 p-3 dark:bg-slate-950" action="{{ route('devices.update', $device) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="school_id" value="{{ $device->school_id }}"><div class="grid grid-cols-2 gap-3"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900" name="name" value="{{ $device->name }}" required><select class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900" name="connection_mode"><option value="sdk" @selected($device->connection_mode === 'sdk')>SDK local</option><option value="adms" @selected($device->connection_mode === 'adms')>ADMS directo</option><option value="hybrid" @selected($device->connection_mode === 'hybrid')>Híbrido</option></select><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900" name="serial_number" value="{{ $device->serial_number }}" placeholder="Serie (obligatoria ADMS)"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900" name="mac_address" value="{{ $device->mac_address }}" placeholder="MAC (obligatoria SDK)"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900" name="model" value="{{ $device->model }}" placeholder="Modelo"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900" name="location" value="{{ $device->location }}" placeholder="Ubicación"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900" name="network" value="{{ $device->network }}" placeholder="Red SDK, ej. 192.168.100"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900" name="ip_address" value="{{ $device->ip_address }}" placeholder="IP opcional"><input type="hidden" name="port" value="{{ $device->port }}"><input class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900" name="device_password" placeholder="Nueva clave (opcional)"><label class="flex items-center gap-2 text-xs font-semibold"><input type="checkbox" name="is_active" value="1" @checked($device->is_active)>Activo</label></div><button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white dark:bg-teal-600" type="submit">Guardar cambios</button></form></details></div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 xl:col-span-2">Todavía no hay lectores registrados.</div>
                        @endforelse

                        @if ($isSuperadmin)
                            <article class="rounded-2xl border border-dashed border-teal-300 bg-teal-50/50 p-5 dark:border-teal-800 dark:bg-teal-950/20">
                                <h3 class="font-extrabold text-teal-950 dark:text-teal-100">Añadir lector</h3><p class="mt-1 text-xs leading-5 text-teal-800/70 dark:text-teal-200/60">Para ADMS registra el número de serie. Para SDK usa la MAC y la red local.</p>
                                <form class="mt-5 space-y-3" action="{{ route('devices.store') }}" method="POST">@csrf
                                    <select class="w-full rounded-xl border border-teal-200 bg-white px-3 py-3 text-sm dark:border-teal-800 dark:bg-slate-900" name="school_id" required>@foreach ($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select>
                                    <input class="w-full rounded-xl border border-teal-200 bg-white px-3 py-3 text-sm dark:border-teal-800 dark:bg-slate-900" name="name" placeholder="Ej. Entrada principal" required>
                                    <select class="w-full rounded-xl border border-teal-200 bg-white px-3 py-3 text-sm dark:border-teal-800 dark:bg-slate-900" name="connection_mode" required><option value="adms">ADMS directo (M2F PRO-LR)</option><option value="sdk">SDK local (lector actual)</option><option value="hybrid">Híbrido</option></select>
                                    <div class="grid grid-cols-2 gap-3"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 font-mono text-sm dark:border-teal-800 dark:bg-slate-900" name="serial_number" placeholder="Número de serie ADMS"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 font-mono text-sm dark:border-teal-800 dark:bg-slate-900" name="mac_address" placeholder="MAC para SDK"></div>
                                    <div class="grid grid-cols-2 gap-3"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 text-sm dark:border-teal-800 dark:bg-slate-900" name="model" value="M2F PRO-LR" placeholder="Modelo"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 text-sm dark:border-teal-800 dark:bg-slate-900" name="location" placeholder="Ubicación"></div>
                                    <div class="grid grid-cols-2 gap-3"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 font-mono text-sm dark:border-teal-800 dark:bg-slate-900" name="network" placeholder="Red SDK, ej. 192.168.100"><input class="rounded-xl border border-teal-200 bg-white px-3 py-3 font-mono text-sm dark:border-teal-800 dark:bg-slate-900" name="ip_address" placeholder="IP opcional"></div>
                                    <input type="hidden" name="port" value="4370">
                                    <button class="w-full rounded-xl bg-amber-300 px-4 py-3 text-sm font-black text-teal-950 transition hover:bg-amber-200" type="submit">Añadir y conectar</button>
                                </form>
                            </article>
                        @endif
                    </div>
                </section>

                <section id="enrollment" class="scroll-mt-32 grid gap-5 xl:grid-cols-[.9fr_1.1fr]">
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">SDK biométrico</p><h2 class="mt-1 text-xl font-black">Registrar una huella</h2><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Selecciona al estudiante y el lector. El dispositivo pedirá tres capturas del dedo.</p><form class="mt-6 space-y-4" action="{{ route('devices.enroll') }}" method="POST">@csrf<label class="block text-sm font-bold">Estudiante<select class="mt-2 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950" name="student_id" required><option value="">Seleccionar estudiante</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->nombre }} {{ $student->apellido }} · {{ $student->curso }} · ID {{ $student->id_lector }}</option>@endforeach</select></label><label class="block text-sm font-bold">Lector<select class="mt-2 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950" name="biometric_device_id" required><option value="">Seleccionar lector</option>@foreach ($devices->where('is_active', true) as $device)<option value="{{ $device->id }}">{{ $device->name }} · {{ $device->school?->short_name ?? $device->school?->name }}</option>@endforeach</select></label><label class="block text-sm font-bold">Dedo<select class="mt-2 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950" name="finger_index" required>@foreach ($fingerNames as $index => $finger)<option value="{{ $index }}">{{ $finger }}</option>@endforeach</select></label><button class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-3 text-sm font-black text-white shadow-lg shadow-teal-700/15 hover:bg-teal-600" type="submit"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a5 5 0 00-5 5v3m10-3a5 5 0 00-5-5m-8 9v1a8 8 0 0016 0v-1M8 12v1a4 4 0 008 0v-1"/></svg>Iniciar registro en el lector</button></form></article>
                    <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="border-b border-stone-100 px-5 py-5 dark:border-slate-800"><h2 class="font-black">Estado de registros</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Solicitudes recientes de huellas enviadas al agente.</p></div><div class="divide-y divide-stone-100 dark:divide-slate-800">@forelse ($enrollments as $enrollment)@php($enrollmentColor = match($enrollment->status) {'enrolled' => 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-400/15', 'failed' => 'text-rose-700 bg-rose-100 dark:text-rose-300 dark:bg-rose-400/15', 'processing' => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-400/15', default => 'text-sky-700 bg-sky-100 dark:text-sky-300 dark:bg-sky-400/15'})<div class="flex items-center justify-between gap-4 px-5 py-4"><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $enrollment->student?->nombre }} {{ $enrollment->student?->apellido }}</p><p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $enrollment->device?->name }} · {{ $fingerNames[$enrollment->finger_index] ?? 'Dedo '.$enrollment->finger_index }}</p></div><div class="flex shrink-0 flex-col items-end gap-2"><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $enrollmentColor }}">{{ match($enrollment->status) {'enrolled' => 'Registrada', 'failed' => 'Falló', 'processing' => 'En proceso', default => 'Pendiente'} }}</span>@if ($enrollment->status === 'failed')<form action="{{ route('devices.enrollments.verify', $enrollment) }}" method="POST">@csrf<button class="text-[11px] font-black text-teal-700 underline decoration-teal-300 underline-offset-4 dark:text-teal-300" type="submit">Verificar plantilla</button></form>@endif</div></div>@empty<div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No hay solicitudes de huellas todavía.</div>@endforelse</div></article>
                </section>

                @if ($isSuperadmin)
                    @include('dashboards.admin-management')
                @endif

                <section class="grid gap-5 xl:grid-cols-5">
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3 dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><div><h2 class="text-lg font-black">Tendencia semanal</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Entradas registradas durante los últimos cinco días.</p></div><span class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-bold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">5 días</span></div><div class="mt-8 grid h-52 grid-cols-5 items-end gap-3 sm:gap-5">@foreach ($tendenciaSemanal as $dia)<div class="flex h-full flex-col justify-end text-center"><p class="mb-2 text-xs font-bold">{{ $dia['porcentaje'] }}%</p><div class="flex h-36 items-end rounded-t-xl bg-stone-100 dark:bg-slate-800"><div class="w-full rounded-t-xl bg-gradient-to-t from-teal-700 to-teal-400" style="height: {{ max($dia['porcentaje'], 2) }}%"></div></div><p class="mt-3 text-xs font-black uppercase text-slate-500">{{ $dia['dia'] }}</p><p class="text-[11px] text-slate-400">{{ $dia['fecha'] }}</p></div>@endforeach</div></article>
                    <article id="reports" class="scroll-mt-32 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2 dark:border-slate-800 dark:bg-slate-900"><h2 class="text-lg font-black">Asistencia por curso</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Entradas sobre estudiantes registrados.</p><div class="mt-7 space-y-5">@forelse ($asistenciaPorCurso as $curso)<div><div class="mb-2 flex justify-between gap-3 text-sm"><span class="font-bold">{{ $curso['curso'] }}</span><span class="font-semibold text-slate-500">{{ $curso['presentes'] }} / {{ $curso['estudiantes'] }}</span></div><div class="h-2.5 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full bg-gradient-to-r from-teal-600 to-emerald-400" style="width: {{ $curso['porcentaje'] }}%"></div></div></div>@empty<p class="rounded-xl border border-dashed border-stone-200 p-4 text-sm text-slate-500 dark:border-slate-700">Aún no hay estudiantes registrados.</p>@endforelse</div></article>
                </section>

                <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.18em] text-sky-700 dark:text-sky-300">Diagnóstico directo</p>
                            <h2 class="mt-1 text-xl font-black">Eventos ADMS sin filtrar</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Muestra lo recibido del lector aunque el ID biométrico todavía no esté vinculado a un estudiante.</p>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-sky-100 px-3 py-2 text-xs font-bold text-sky-700 dark:bg-sky-400/15 dark:text-sky-300"><span class="h-2 w-2 animate-pulse rounded-full bg-sky-500"></span>Recepción ADMS</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1050px] text-left text-sm">
                            <thead class="bg-stone-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                                <tr><th class="px-6 py-4">Recibido</th><th class="px-6 py-4">ID biométrico</th><th class="px-6 py-4">Lector / IP</th><th class="px-6 py-4">Estudiante</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4">Dato recibido</th></tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-slate-800">
                                @forelse ($recentAdmsEvents as $event)
                                    @php($eventStatus = match ($event->processing_status) {
                                        'processed' => ['Procesado', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300'],
                                        'unmatched' => ['Sin coincidencia', 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300'],
                                        default => ['Pendiente', 'bg-sky-100 text-sky-700 dark:bg-sky-400/15 dark:text-sky-300'],
                                    })
                                    <tr class="align-top hover:bg-stone-50 dark:hover:bg-slate-800/50">
                                        <td class="whitespace-nowrap px-6 py-4"><span class="block font-mono font-bold text-teal-700 dark:text-teal-300">{{ $event->event_at?->format('H:i:s') ?? 'Sin hora' }}</span><span class="mt-1 block text-xs text-slate-400">{{ $event->created_at?->format('d/m/Y H:i:s') }}</span></td>
                                        <td class="px-6 py-4 font-mono font-black">{{ $event->user_id ?: 'No enviado' }}</td>
                                        <td class="px-6 py-4"><span class="font-bold">{{ $event->device?->name ?? 'Lector eliminado' }}</span><span class="mt-1 block font-mono text-xs text-slate-500">{{ $event->device?->ip_address ?? 'IP no disponible' }}</span></td>
                                        <td class="px-6 py-4">@if ($event->student)<span class="font-bold">{{ $event->student->nombre }} {{ $event->student->apellido }}</span><span class="mt-1 block text-xs text-slate-500">{{ $event->student->matricula }} · {{ $event->student->curso }}</span>@else<span class="font-bold text-amber-700 dark:text-amber-300">Sin vincular</span>@endif</td>
                                        <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $eventStatus[1] }}">{{ $eventStatus[0] }}</span>@if ($event->error)<span class="mt-2 block max-w-xs text-xs leading-5 text-rose-600 dark:text-rose-300">{{ $event->error }}</span>@endif</td>
                                        <td class="max-w-sm break-all px-6 py-4 font-mono text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $event->raw_payload }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-6 py-14 text-center text-slate-500" colspan="6">Todavía no se han recibido eventos por ADMS.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="attendance" class="scroll-mt-32 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800"><div><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">En tiempo real</p><h2 class="mt-1 text-xl font-black">Actividad reciente</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Lecturas recibidas hoy desde los dispositivos.</p></div><span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-100 px-3 py-2 text-xs font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>Captura activa</span></div><div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="bg-stone-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-950 dark:text-slate-400"><tr><th class="px-6 py-4">Estudiante</th><th class="px-6 py-4">Matrícula</th><th class="px-6 py-4">Curso</th><th class="px-6 py-4">Lector / escuela</th><th class="px-6 py-4">Hora</th><th class="px-6 py-4">Registro</th></tr></thead><tbody class="divide-y divide-stone-100 dark:divide-slate-800">@forelse ($ultimosRegistros as $registro)<tr class="hover:bg-stone-50 dark:hover:bg-slate-800/50"><td class="px-6 py-4 font-bold">{{ $registro['nombre'] }}</td><td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $registro['matricula'] }}</td><td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $registro['curso'] }}</td><td class="px-6 py-4"><span class="font-bold">{{ $registro['lector'] }}</span>@if ($registro['escuela'])<span class="mt-1 block text-xs text-slate-500">{{ $registro['escuela'] }}</span>@endif</td><td class="px-6 py-4 font-mono font-bold text-teal-700 dark:text-teal-300">{{ $registro['hora'] }}</td><td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $registro['tipo'] === 'Entrada' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' }}">{{ $registro['tipo'] }}</span></td></tr>@empty<tr><td class="px-6 py-14 text-center text-slate-500" colspan="6">Aún no se han recibido registros hoy.</td></tr>@endforelse</tbody></table></div></section>

                <footer class="flex flex-col gap-2 border-t border-stone-200 py-6 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"><p>Portal escolar · Control biométrico seguro</p><p>SDK local + preparación ADMS · {{ now()->year }}</p></footer>
            </main>
        </div>
    </div>
</body>
</html>

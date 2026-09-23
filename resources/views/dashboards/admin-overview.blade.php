<section id="overview" class="space-y-5" data-overview-console>
    @php
        $visibleLateCount = $ultimosRegistros->where('is_late', true)->unique('matricula')->count();
        $overviewStatusMeta = [
            'online' => ['Conectado', 'bg-emerald-400', 'text-emerald-400'],
            'delayed' => ['Advertencia', 'bg-amber-400', 'text-amber-400'],
            'offline' => ['Sin comunicación', 'bg-rose-400', 'text-rose-400'],
            'never_connected' => ['Sin comunicación', 'bg-rose-400', 'text-rose-400'],
            'pending_assignment' => ['Pendiente', 'bg-sky-400', 'text-sky-400'],
            'inactive' => ['Inactivo', 'bg-slate-400', 'text-slate-400'],
        ];
    @endphp

    <header class="flex flex-col gap-4 rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-400">Centro de operaciones</p><h1 class="mt-1 text-2xl font-black text-white">Asistencia de hoy</h1><p class="mt-1 text-sm text-slate-400">{{ $activeSchool?->name ?? 'Red de centros educativos' }} · {{ $analysisDate->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p></div>
        <div class="flex flex-wrap items-center gap-2"><span class="inline-flex items-center gap-2 rounded-lg bg-emerald-400/10 px-3 py-2 text-xs font-black text-emerald-300"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>Monitoreo activo</span>@if ($shareSchool?->public_dashboard_token)<a class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-xs font-black text-slate-950 hover:bg-teal-400" href="{{ route('public.dashboard.show', $shareSchool->public_dashboard_token) }}" target="_blank" rel="noopener">Abrir visualización <span aria-hidden="true">↗</span></a>@endif</div>
    </header>

    <section class="grid grid-cols-2 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm lg:grid-cols-4 dark:border-slate-800 dark:bg-slate-900" aria-label="Resumen de asistencia">
        @foreach ([
            ['Estudiantes', $resumen['total_estudiantes'], 'text-sky-600 dark:text-sky-400', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'],
            ['Presentes', $resumen['presentes_hoy'], 'text-emerald-600 dark:text-emerald-400', 'm5 12 4 4L19 6'],
            ['Tardanzas recientes', $visibleLateCount, 'text-amber-600 dark:text-amber-400', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['Ausentes', $resumen['ausentes_hoy'], 'text-rose-600 dark:text-rose-400', 'M6 18 18 6M6 6l12 12'],
        ] as [$label, $value, $color, $icon])
            <article class="border-stone-200 px-4 py-4 even:border-l lg:border-l lg:first:border-l-0 dark:border-slate-800"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $label }}</p><p class="mt-1 text-3xl font-black tabular-nums {{ $color }}">{{ number_format($value) }}</p></div><svg class="h-5 w-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg></div><p class="mt-1 text-[11px] text-slate-400">Actualizado {{ now()->format('H:i') }}</p></article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-12">
        @if (false)
        <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm xl:col-span-7 dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between gap-4 border-b border-stone-100 px-5 py-4 dark:border-slate-800"><div><p class="text-[11px] font-black uppercase tracking-[.18em] text-emerald-600 dark:text-emerald-400">Flujo de entradas</p><h2 class="mt-1 text-lg font-black">Actividad en vivo</h2></div><span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-black text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>Recibiendo</span></header>
            <div class="divide-y divide-stone-100 dark:divide-slate-800">
                @forelse ($ultimosRegistros->take(6) as $registro)
                    <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-4 px-5 py-3"><time class="font-mono text-sm font-black text-teal-600 dark:text-teal-300">{{ $registro['hora'] }}</time><div class="min-w-0"><p class="truncate text-sm font-black">{{ $registro['nombre'] }}</p><p class="mt-0.5 truncate text-xs text-slate-500">{{ $registro['curso'] }} · {{ $registro['lector'] }}</p></div><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $registro['is_late'] ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' }}">{{ $registro['is_late'] ? 'Tardanza' : $registro['tipo'] }}</span></div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-slate-500">Aún no se han recibido registros hoy.</p>
                @endforelse
            </div>
        </article>
        @endif

        <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm xl:col-span-12 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-[.18em] text-sky-600 dark:text-sky-400">Infraestructura</p><h2 class="mt-1 text-lg font-black">Estado de lectores</h2></div><a class="text-xs font-black text-teal-600 hover:text-teal-500 dark:text-teal-300" href="{{ route($dashboardPageRoute, 'devices') }}">Administrar →</a></div>
            <div class="mt-4 grid grid-cols-2 gap-2"><div class="rounded-xl bg-emerald-50 px-3 py-3 dark:bg-emerald-400/10"><p class="text-xs font-bold text-emerald-700 dark:text-emerald-300">En línea</p><p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $deviceSummary['online'] }}</p></div><div class="rounded-xl bg-rose-50 px-3 py-3 dark:bg-rose-400/10"><p class="text-xs font-bold text-rose-700 dark:text-rose-300">Sin comunicación</p><p class="mt-1 text-2xl font-black text-rose-700 dark:text-rose-300">{{ $deviceSummary['offline'] }}</p></div></div>
            <div class="mt-4 grid gap-x-6 sm:grid-cols-2 xl:grid-cols-3">@forelse ($devices->take(6) as $device)@php([$readerLabel, $readerDot, $readerText] = $overviewStatusMeta[$device->connectionStatus()])<div class="flex items-center justify-between gap-4 border-t border-stone-100 py-3 dark:border-slate-800"><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $device->name }}</p><p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $device->location ?: $device->school?->short_name ?: $device->school?->name }}</p></div><div class="shrink-0 text-right"><span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase {{ $readerText }}"><span class="h-2 w-2 rounded-full {{ $readerDot }}"></span>{{ $readerLabel }}</span><p class="mt-1 text-[10px] text-slate-400">{{ $device->last_seen_at?->diffForHumans() ?? 'Nunca' }}</p></div></div>@empty<p class="py-8 text-center text-sm text-slate-500">No hay lectores registrados.</p>@endforelse</div>
        </article>
    </section>

    <section class="grid gap-5 xl:grid-cols-12">
        <article id="reports" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm xl:col-span-7 dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between"><div><h2 class="text-lg font-black">Asistencia por curso</h2><p class="mt-1 text-sm text-slate-500">Presentes sobre estudiantes registrados.</p></div><span class="text-xs font-black text-teal-600 dark:text-teal-300">{{ $resumen['porcentaje_hoy'] }}% general</span></div><div class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">@forelse ($asistenciaPorCurso as $curso)<div><div class="mb-2 flex justify-between gap-3 text-xs"><span class="truncate font-bold">{{ $curso['curso'] }}</span><span class="shrink-0 font-semibold text-slate-500">{{ $curso['presentes'] }}/{{ $curso['estudiantes'] }} · {{ $curso['porcentaje'] }}%</span></div><div class="h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-slate-800"><div class="h-full rounded-full bg-teal-500" style="width: {{ $curso['porcentaje'] }}%"></div></div></div>@empty<p class="text-sm text-slate-500">Aún no hay estudiantes registrados.</p>@endforelse</div></article>

        <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm xl:col-span-5 dark:border-slate-800 dark:bg-slate-900"><div class="flex items-start justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-[.18em] text-teal-600 dark:text-teal-400">Pantalla institucional</p><h2 class="mt-1 text-lg font-black">Enlace público</h2></div>@if ($shareSchool?->public_dashboard_token)<a class="rounded-lg bg-teal-600 px-3 py-2 text-xs font-black text-white" href="{{ route('public.dashboard.show', $shareSchool->public_dashboard_token) }}" target="_blank" rel="noopener">Abrir visualización ↗</a>@endif</div>
            @if ($shareSchool)
                @if ($shareSchool->public_dashboard_token)
                    @php($publicDashboardUrl = route('public.dashboard.show', $shareSchool->public_dashboard_token))
                    <input class="mt-4 w-full rounded-lg border border-stone-200 bg-stone-50 px-3 py-2.5 font-mono text-[11px] dark:border-slate-700 dark:bg-slate-950" value="{{ $publicDashboardUrl }}" readonly onclick="this.select()">
                    <div class="mt-3 flex gap-2"><form action="{{ route('schools.public-dashboard.generate', $shareSchool) }}" method="POST">@csrf<button class="rounded-lg px-3 py-2 text-xs font-bold text-slate-600 ring-1 ring-inset ring-stone-200 dark:text-slate-300 dark:ring-slate-700" type="submit">Regenerar</button></form><form action="{{ route('schools.public-dashboard.revoke', $shareSchool) }}" method="POST">@csrf @method('DELETE')<button class="rounded-lg px-3 py-2 text-xs font-bold text-rose-600 ring-1 ring-inset ring-rose-200 dark:text-rose-300 dark:ring-rose-900" type="submit">Desactivar</button></form></div>
                @else
                    <p class="mt-3 text-sm text-slate-500">Genera un enlace seguro para la pantalla de dirección.</p><form class="mt-4" action="{{ route('schools.public-dashboard.generate', $shareSchool) }}" method="POST">@csrf<button class="rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-black text-white" type="submit">Generar enlace público</button></form>
                @endif
            @else
                <p class="mt-3 text-sm text-slate-500">Selecciona una escuela para administrar su visualización.</p>
            @endif
        </article>
    </section>

    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Tendencia semanal</h2><p class="mt-1 text-sm text-slate-500">Porcentaje de estudiantes presentes durante los últimos cinco días.</p></div><span class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-black text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">5 días</span></div>
        <div class="mt-6 grid h-44 grid-cols-5 items-end gap-3 sm:h-52 sm:gap-6">
            @foreach ($tendenciaSemanal as $dia)
                <div class="flex h-full min-w-0 flex-col justify-end text-center"><p class="mb-2 text-xs font-black tabular-nums text-slate-700 dark:text-slate-200">{{ $dia['porcentaje'] }}%</p><div class="flex h-28 items-end overflow-hidden rounded-t-lg bg-stone-100 sm:h-36 dark:bg-slate-800"><div class="w-full rounded-t-lg bg-teal-500" style="height: {{ max($dia['porcentaje'], 4) }}%"></div></div><p class="mt-2 text-xs font-black uppercase text-slate-600 dark:text-slate-300">{{ $dia['dia'] }}</p><p class="mt-0.5 text-[11px] text-slate-400">{{ $dia['fecha'] }}</p></div>
            @endforeach
        </div>
    </article>
</section>

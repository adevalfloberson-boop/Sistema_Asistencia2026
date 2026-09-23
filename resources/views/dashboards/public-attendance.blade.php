<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="dark">
    <title>Asistencia en vivo · {{ $school->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased lg:h-screen lg:overflow-hidden">
    <main class="mx-auto flex min-h-screen max-w-[1920px] flex-col gap-4 px-5 py-5 sm:px-7 lg:h-screen lg:min-h-0 lg:px-9 lg:py-7">
        <header class="flex shrink-0 flex-col gap-4 border-b border-slate-800 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-500 text-sm font-black text-slate-950">{{ strtoupper(substr($school->short_name ?: $school->name, 0, 2)) }}</span><div><p class="text-xs font-black uppercase tracking-[.2em] text-teal-400">Asistencia en tiempo real</p><h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl lg:text-4xl">{{ $school->name }}</h1></div></div>
            <div class="flex items-end justify-between gap-8 sm:justify-end"><div class="text-right"><p class="text-sm font-bold uppercase tracking-[.12em] text-slate-400" data-public-date>{{ now()->locale('es')->isoFormat('D MMM YYYY') }}</p><p class="mt-1 font-mono text-2xl font-black tabular-nums lg:text-3xl" data-public-clock>{{ now()->format('H:i:s') }}</p></div><div class="mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-emerald-300" data-live-status><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400"></span><span>Sistema activo</span></div></div>
        </header>

        <section class="grid shrink-0 grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores de asistencia">
            @foreach ([['Presentes', 'present', $summary['present'], 'text-emerald-400', 'bg-emerald-400'], ['Tardanzas', 'late', $summary['late'], 'text-amber-400', 'bg-amber-400'], ['Ausentes', 'absent', $summary['absent'], 'text-rose-400', 'bg-rose-400'], ['Estudiantes', 'total', $summary['total'], 'text-sky-400', 'bg-sky-400']] as [$label, $key, $value, $color, $dot])
                <article class="relative overflow-hidden rounded-xl bg-slate-900 px-5 py-4 ring-1 ring-inset ring-slate-800"><span class="absolute inset-y-0 left-0 w-1 {{ $dot }}"></span><p class="text-xs font-black uppercase tracking-[.14em] text-slate-400">{{ $label }}</p><p class="mt-1 text-4xl font-black tabular-nums sm:text-5xl lg:text-6xl {{ $color }}" data-summary="{{ $key }}">{{ $value }}</p></article>
            @endforeach
        </section>

        <section class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.25fr_.75fr]">
            <article class="flex min-h-0 flex-col overflow-hidden rounded-xl bg-slate-900 ring-1 ring-inset ring-slate-800">
                <header class="flex shrink-0 items-center justify-between border-b border-slate-800 px-5 py-3"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-400">Últimos registros</p><h2 class="mt-0.5 text-xl font-black">Actividad en vivo</h2></div><span class="text-xs font-bold text-slate-400">Actualización automática</span></header>
                <div class="min-h-0 flex-1 divide-y divide-slate-800 overflow-hidden" data-live-records>
                    @forelse ($records->take(6) as $record)
                        <div class="grid grid-cols-[5rem_minmax(0,1fr)_auto] items-center gap-4 px-5 py-3 lg:grid-cols-[6rem_minmax(0,1fr)_auto] lg:py-3.5"><time class="font-mono text-xl font-black tabular-nums text-teal-300 lg:text-2xl">{{ substr($record['time'], 0, 5) }}</time><div class="min-w-0"><p class="truncate text-base font-black lg:text-lg">{{ $record['name'] }}</p><p class="mt-0.5 truncate text-xs font-semibold text-slate-400 lg:text-sm">{{ $record['course'] }}</p></div><span class="rounded-full px-3 py-1.5 text-xs font-black uppercase {{ $record['status'] === 'Tardanza' ? 'bg-amber-400/10 text-amber-300' : ($record['type'] === 'Entrada' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-sky-400/10 text-sky-300') }}">{{ $record['status'] }}</span></div>
                    @empty
                        <div class="flex h-full min-h-48 items-center justify-center px-6 text-center"><div><span class="mx-auto block h-3 w-3 animate-pulse rounded-full bg-teal-400"></span><p class="mt-4 text-base font-bold text-slate-400">Esperando el primer registro de hoy</p></div></div>
                    @endforelse
                </div>
            </article>

            <article class="flex min-h-0 flex-col rounded-xl bg-slate-900 p-5 ring-1 ring-inset ring-slate-800"><header class="shrink-0"><div class="flex items-end justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-sky-400">Cobertura por grupo</p><h2 class="mt-0.5 text-xl font-black">Asistencia por curso</h2></div><p class="text-3xl font-black text-teal-300" data-summary="percentage">{{ $summary['percentage'] }}%</p></div></header><div class="mt-5 min-h-0 flex-1 space-y-4 overflow-hidden" data-course-summary>@forelse ($courses as $course)<div><div class="mb-2 flex justify-between gap-3"><span class="truncate text-sm font-black">{{ $course['name'] }}</span><span class="shrink-0 text-sm font-bold text-slate-400">{{ $course['present'] }}/{{ $course['total'] }} · {{ $course['percentage'] }}%</span></div><div class="h-3 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full bg-teal-400" style="width: {{ $course['percentage'] }}%"></div></div></div>@empty<div class="flex h-full items-center justify-center text-sm text-slate-400">No hay cursos registrados.</div>@endforelse</div></article>
        </section>

        <footer class="flex shrink-0 items-center justify-between border-t border-slate-800 pt-3 text-[11px] font-semibold text-slate-500"><p>Información institucional · Solo lectura</p><p>Última actualización: <span class="font-mono text-slate-300" data-updated-at>{{ now()->format('H:i:s') }}</span></p></footer>
    </main>

    <script>
        (() => {
            const endpoint = @json(route('public.dashboard.activity', $school->public_dashboard_token));
            const recordsContainer = document.querySelector('[data-live-records]');
            const coursesContainer = document.querySelector('[data-course-summary]');
            const status = document.querySelector('[data-live-status]');
            const updatedAt = document.querySelector('[data-updated-at]');
            const clock = document.querySelector('[data-public-clock]');
            const date = document.querySelector('[data-public-date]');
            const text = (tag, value, className = '') => { const element = document.createElement(tag); element.textContent = value; element.className = className; return element; };
            const badgeClasses = (record) => record.status === 'Tardanza' ? 'bg-amber-400/10 text-amber-300' : (record.type === 'Entrada' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-sky-400/10 text-sky-300');

            const renderRecords = (records) => {
                recordsContainer.replaceChildren();
                if (! records.length) { recordsContainer.append(text('div', 'Esperando el primer registro de hoy', 'flex h-full min-h-48 items-center justify-center px-6 text-center text-base font-bold text-slate-400')); return; }
                records.slice(0, 6).forEach((record) => { const row = document.createElement('div'); row.className = 'grid grid-cols-[5rem_minmax(0,1fr)_auto] items-center gap-4 px-5 py-3 lg:grid-cols-[6rem_minmax(0,1fr)_auto] lg:py-3.5'; const info = document.createElement('div'); info.className = 'min-w-0'; info.append(text('p', record.name, 'truncate text-base font-black lg:text-lg'), text('p', record.course, 'mt-0.5 truncate text-xs font-semibold text-slate-400 lg:text-sm')); row.append(text('time', record.time.slice(0, 5), 'font-mono text-xl font-black tabular-nums text-teal-300 lg:text-2xl'), info, text('span', record.status, `rounded-full px-3 py-1.5 text-xs font-black uppercase ${badgeClasses(record)}`)); recordsContainer.append(row); });
            };

            const renderCourses = (courses) => {
                coursesContainer.replaceChildren();
                if (! courses.length) { coursesContainer.append(text('div', 'No hay cursos registrados.', 'flex h-full items-center justify-center text-sm text-slate-400')); return; }
                courses.forEach((course) => { const item = document.createElement('div'); const labels = document.createElement('div'); labels.className = 'mb-2 flex justify-between gap-3'; labels.append(text('span', course.name, 'truncate text-sm font-black'), text('span', `${course.present}/${course.total} · ${course.percentage}%`, 'shrink-0 text-sm font-bold text-slate-400')); const track = document.createElement('div'); track.className = 'h-3 overflow-hidden rounded-full bg-slate-800'; const bar = document.createElement('div'); bar.className = 'h-full rounded-full bg-teal-400'; bar.style.width = `${course.percentage}%`; track.append(bar); item.append(labels, track); coursesContainer.append(item); });
            };

            const updateClock = () => { const now = new Date(); clock.textContent = now.toLocaleTimeString('es-DO', { hour12: false }); date.textContent = now.toLocaleDateString('es-DO', { day: '2-digit', month: 'short', year: 'numeric' }).replaceAll('.', '').toUpperCase(); };
            const refresh = async () => {
                try {
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' }); if (! response.ok) throw new Error('No fue posible actualizar'); const data = await response.json();
                    Object.entries(data.summary).forEach(([key, value]) => { const element = document.querySelector(`[data-summary="${key}"]`); if (element) element.textContent = key === 'percentage' ? `${value}%` : value; });
                    renderRecords(data.records); renderCourses(data.courses); updatedAt.textContent = data.generated_at; status.className = 'mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-emerald-300'; status.querySelector('span:last-child').textContent = 'Sistema activo';
                } catch (_) { status.className = 'mb-1 inline-flex items-center gap-2 rounded-full bg-amber-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-amber-300'; status.querySelector('span:last-child').textContent = 'Reconectando'; }
            };
            updateClock(); window.setInterval(updateClock, 1000); window.setInterval(refresh, 5000);
        })();
    </script>
</body>
</html>

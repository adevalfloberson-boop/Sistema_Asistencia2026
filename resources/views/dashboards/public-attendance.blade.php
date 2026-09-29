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
<body class="min-h-screen overflow-y-auto bg-slate-950 text-white antialiased">
    <main class="mx-auto flex min-h-screen max-w-[1920px] flex-col gap-4 px-5 py-5 sm:px-7 lg:px-9 lg:py-7">
        <header class="flex shrink-0 flex-col gap-4 border-b border-slate-800 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-500 text-sm font-black text-slate-950">{{ strtoupper(substr($school->short_name ?: $school->name, 0, 2)) }}</span><div><p class="text-xs font-black uppercase tracking-[.2em] text-teal-400">Asistencia en tiempo real</p><h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl lg:text-4xl">{{ $school->name }}</h1></div></div>
            <div class="flex items-end justify-between gap-8 sm:justify-end"><div class="text-right"><p class="text-sm font-bold uppercase tracking-[.12em] text-slate-400" data-public-date>{{ now()->locale('es')->isoFormat('D MMM YYYY') }}</p><p class="mt-1 font-mono text-2xl font-black tabular-nums lg:text-3xl" data-public-clock>{{ now()->format('H:i:s') }}</p></div><div class="mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-emerald-300" data-live-status><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400"></span><span>Sistema activo</span></div></div>
        </header>

        <section class="grid shrink-0 grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores de asistencia">
            @foreach ([['Presentes', 'present', $summary['present'], 'text-emerald-400', 'bg-emerald-400'], ['Tardanzas', 'late', $summary['late'], 'text-amber-400', 'bg-amber-400'], ['Ausentes', 'absent', $summary['absent'], 'text-rose-400', 'bg-rose-400'], ['Estudiantes', 'total', $summary['total'], 'text-sky-400', 'bg-sky-400']] as [$label, $key, $value, $color, $dot])
                <button class="relative overflow-hidden rounded-xl bg-slate-900 px-5 py-4 text-left ring-1 ring-inset ring-slate-800 transition hover:-translate-y-0.5 hover:bg-slate-800 hover:ring-slate-700" type="button" data-open-dialog="public-{{ $key }}-dialog"><span class="absolute inset-y-0 left-0 w-1 {{ $dot }}"></span><p class="text-xs font-black uppercase tracking-[.14em] text-slate-400">{{ $label }}</p><p class="mt-1 text-4xl font-black tabular-nums sm:text-5xl lg:text-6xl {{ $color }}" data-summary="{{ $key }}">{{ $value }}</p><p class="mt-2 text-[10px] font-black uppercase tracking-wider text-slate-500">Ver detalle</p></button>
            @endforeach
        </section>

        <dialog id="public-total-dialog" class="m-auto w-[min(640px,94vw)] overflow-hidden rounded-3xl border-0 bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/85"><header class="flex items-start justify-between bg-slate-950 p-6"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-400">Matrícula oficial</p><h2 class="mt-1 text-2xl font-black">Estudiantes registrados</h2></div><button class="rounded-full bg-white/10 px-3 py-2 text-xl" type="button" data-close-dialog>×</button></header><div class="p-6"><div class="rounded-2xl bg-teal-400/10 p-5 text-center"><p class="text-xs font-black uppercase text-teal-300">Total</p><p class="mt-1 text-5xl font-black text-teal-300" data-summary="total">{{ $summary['total'] }}</p></div><div class="mt-4 grid grid-cols-2 gap-4"><div class="rounded-2xl bg-fuchsia-400/10 p-5"><p class="text-xs font-black uppercase text-fuchsia-300">Hembras</p><p class="mt-2 text-4xl font-black text-fuchsia-300" data-summary="female">{{ $summary['female'] }}</p></div><div class="rounded-2xl bg-sky-400/10 p-5"><p class="text-xs font-black uppercase text-sky-300">Varones</p><p class="mt-2 text-4xl font-black text-sky-300" data-summary="male">{{ $summary['male'] }}</p></div></div></div></dialog>

        <dialog id="public-present-dialog" class="m-auto w-[min(640px,94vw)] overflow-hidden rounded-3xl border-0 bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/85"><header class="flex items-start justify-between bg-slate-950 p-6"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-emerald-400">Asistencia de hoy</p><h2 class="mt-1 text-2xl font-black">Estudiantes presentes</h2></div><button class="rounded-full bg-white/10 px-3 py-2 text-xl" type="button" data-close-dialog>×</button></header><div class="p-6"><div class="rounded-2xl bg-emerald-400/10 p-5 text-center"><p class="text-xs font-black uppercase text-emerald-300">Total presente</p><p class="mt-1 text-5xl font-black text-emerald-300" data-summary="present">{{ $summary['present'] }}</p></div><div class="mt-4 grid grid-cols-2 gap-4"><div class="rounded-2xl bg-fuchsia-400/10 p-5"><p class="text-xs font-black uppercase text-fuchsia-300">Hembras</p><p class="mt-2 text-4xl font-black text-fuchsia-300" data-summary="present_female">{{ $summary['present_female'] }}</p></div><div class="rounded-2xl bg-sky-400/10 p-5"><p class="text-xs font-black uppercase text-sky-300">Varones</p><p class="mt-2 text-4xl font-black text-sky-300" data-summary="present_male">{{ $summary['present_male'] }}</p></div></div></div></dialog>

        @foreach ([['late', 'Estudiantes con tardanza', $rosters['late'], 'text-amber-400', 'bg-amber-400/10 text-amber-300'], ['absent', 'Estudiantes que no asistieron', $rosters['absent'], 'text-rose-400', 'bg-rose-400/10 text-rose-300']] as [$key, $title, $roster, $headingColor, $badgeColor])
            <dialog id="public-{{ $key }}-dialog" class="m-auto max-h-[90vh] w-[min(880px,95vw)] overflow-hidden rounded-3xl border-0 bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/85" data-public-roster><header class="flex items-start justify-between bg-slate-950 p-6"><div><p class="text-[11px] font-black uppercase tracking-[.2em] {{ $headingColor }}">Seguimiento del día</p><h2 class="mt-1 text-2xl font-black">{{ $title }}</h2><p class="mt-1 text-sm text-slate-400">{{ $roster->count() }} estudiante(s)</p></div><button class="rounded-full bg-white/10 px-3 py-2 text-xl" type="button" data-close-dialog>×</button></header><div class="border-b border-slate-800 bg-slate-950/50 p-4"><div class="grid gap-3 sm:grid-cols-[1fr_220px]"><input class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm outline-none focus:border-teal-400" type="search" placeholder="Buscar nombre o matrícula..." data-public-roster-search><select class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm" data-public-roster-course><option value="">Todos los cursos</option>@foreach ($courses->pluck('name') as $course)<option value="{{ Str::lower($course) }}">{{ $course }}</option>@endforeach</select></div></div><div class="max-h-[55vh] overflow-y-auto p-4"><div class="grid gap-3 sm:grid-cols-2">@foreach ($roster as $student)<article class="rounded-2xl border border-slate-800 bg-slate-950/40 p-4" data-public-roster-row data-search="{{ Str::lower($student['name'].' '.$student['registration']) }}" data-course="{{ Str::lower($student['course']) }}"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $student['name'] }}</h3><p class="mt-1 text-xs text-slate-400">{{ $student['registration'] }} · {{ $student['course'] }}</p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $badgeColor }}">{{ $key === 'late' ? 'Tardanza' : 'Ausente' }}</span></div><p class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-500">{{ $student['sex'] }}</p></article>@endforeach</div><p class="hidden p-10 text-center text-sm text-slate-400" data-public-roster-empty>No hay estudiantes que coincidan con los filtros.</p>@if ($roster->isEmpty())<p class="p-10 text-center text-slate-400">No hay estudiantes en esta condición.</p>@endif</div></dialog>
        @endforeach

        <section class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1.25fr_.75fr]">
            <article class="flex min-h-0 flex-col overflow-hidden rounded-xl bg-slate-900 ring-1 ring-inset ring-slate-800">
                <header class="flex shrink-0 items-center justify-between border-b border-slate-800 px-5 py-3"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-400">Últimos registros</p><h2 class="mt-0.5 text-xl font-black">Actividad en vivo</h2></div><span class="text-xs font-bold text-slate-400">Actualización automática</span></header>
                <div class="max-h-[34rem] min-h-0 flex-1 divide-y divide-slate-800 overflow-y-auto overscroll-contain" data-live-records>
                    @forelse ($records as $record)
                        <div class="grid grid-cols-[5rem_minmax(0,1fr)_auto] items-center gap-4 px-5 py-3 lg:grid-cols-[6rem_minmax(0,1fr)_auto] lg:py-3.5"><time class="font-mono text-xl font-black tabular-nums text-teal-300 lg:text-2xl">{{ substr($record['time'], 0, 5) }}</time><div class="min-w-0"><p class="truncate text-base font-black lg:text-lg">{{ $record['name'] }}</p><p class="mt-0.5 truncate text-xs font-semibold text-slate-400 lg:text-sm">{{ $record['course'] }}</p></div><span class="rounded-full px-3 py-1.5 text-xs font-black uppercase {{ $record['status'] === 'Tardanza' ? 'bg-amber-400/10 text-amber-300' : ($record['type'] === 'Entrada' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-sky-400/10 text-sky-300') }}">{{ $record['status'] }}</span></div>
                    @empty
                        <div class="flex h-full min-h-48 items-center justify-center px-6 text-center"><div><span class="mx-auto block h-3 w-3 animate-pulse rounded-full bg-teal-400"></span><p class="mt-4 text-base font-bold text-slate-400">Esperando el primer registro de hoy</p></div></div>
                    @endforelse
                </div>
            </article>

            <article class="flex min-h-0 flex-col rounded-xl bg-slate-900 p-5 ring-1 ring-inset ring-slate-800"><header class="shrink-0"><div class="flex items-end justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-sky-400">Cobertura por grupo</p><h2 class="mt-0.5 text-xl font-black">Asistencia por curso</h2></div><p class="text-3xl font-black text-teal-300" data-summary="percentage">{{ $summary['percentage'] }}%</p></div></header><div class="mt-5 min-h-0 flex-1 space-y-4 overflow-y-auto" data-course-summary>@forelse ($courses as $course)<div><div class="flex items-center justify-between gap-3"><span class="truncate text-sm font-black">{{ $course['name'] }}</span><span class="text-xs font-bold text-slate-400">{{ $course['present'] }}/{{ $course['total'] }} · {{ $course['percentage'] }}%</span></div><div class="mt-2 grid grid-cols-2 gap-2 text-[10px] font-black uppercase"><span class="rounded-lg bg-fuchsia-400/10 px-2 py-1.5 text-fuchsia-300">H {{ $course['present_female'] }}/{{ $course['female'] }}</span><span class="rounded-lg bg-sky-400/10 px-2 py-1.5 text-sky-300">V {{ $course['present_male'] }}/{{ $course['male'] }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full bg-teal-400" style="width: {{ $course['percentage'] }}%"></div></div></div>@empty<div class="flex h-full items-center justify-center text-sm text-slate-400">No hay cursos registrados.</div>@endforelse</div></article>
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

            document.querySelectorAll('[data-public-roster]').forEach((dialog) => {
                const search = dialog.querySelector('[data-public-roster-search]');
                const course = dialog.querySelector('[data-public-roster-course]');
                const empty = dialog.querySelector('[data-public-roster-empty]');
                const filter = () => { let visible = 0; const query = search.value.trim().toLocaleLowerCase(); dialog.querySelectorAll('[data-public-roster-row]').forEach((row) => { const show = row.dataset.search.includes(query) && (! course.value || row.dataset.course === course.value); row.classList.toggle('hidden', ! show); visible += show ? 1 : 0; }); empty.classList.toggle('hidden', visible > 0); };
                search.addEventListener('input', filter);
                course.addEventListener('change', filter);
            });

            const renderRecords = (records) => {
                recordsContainer.replaceChildren();
                if (! records.length) { recordsContainer.append(text('div', 'Esperando el primer registro de hoy', 'flex h-full min-h-48 items-center justify-center px-6 text-center text-base font-bold text-slate-400')); return; }
                records.forEach((record) => { const row = document.createElement('div'); row.className = 'grid grid-cols-[5rem_minmax(0,1fr)_auto] items-center gap-4 px-5 py-3 lg:grid-cols-[6rem_minmax(0,1fr)_auto] lg:py-3.5'; const info = document.createElement('div'); info.className = 'min-w-0'; info.append(text('p', record.name, 'truncate text-base font-black lg:text-lg'), text('p', record.course, 'mt-0.5 truncate text-xs font-semibold text-slate-400 lg:text-sm')); row.append(text('time', record.time.slice(0, 5), 'font-mono text-xl font-black tabular-nums text-teal-300 lg:text-2xl'), info, text('span', record.status, `rounded-full px-3 py-1.5 text-xs font-black uppercase ${badgeClasses(record)}`)); recordsContainer.append(row); });
            };

            const renderCourses = (courses) => {
                coursesContainer.replaceChildren();
                if (! courses.length) { coursesContainer.append(text('div', 'No hay cursos registrados.', 'flex h-full items-center justify-center text-sm text-slate-400')); return; }
                courses.forEach((course) => { const item = document.createElement('div'); const labels = document.createElement('div'); labels.className = 'flex items-center justify-between gap-3'; labels.append(text('span', course.name, 'truncate text-sm font-black'), text('span', `${course.present}/${course.total} · ${course.percentage}%`, 'text-xs font-bold text-slate-400')); const gender = document.createElement('div'); gender.className = 'mt-2 grid grid-cols-2 gap-2 text-[10px] font-black uppercase'; gender.append(text('span', `H ${course.present_female}/${course.female}`, 'rounded-lg bg-fuchsia-400/10 px-2 py-1.5 text-fuchsia-300'), text('span', `V ${course.present_male}/${course.male}`, 'rounded-lg bg-sky-400/10 px-2 py-1.5 text-sky-300')); const track = document.createElement('div'); track.className = 'mt-2 h-2 overflow-hidden rounded-full bg-slate-800'; const bar = document.createElement('div'); bar.className = 'h-full rounded-full bg-teal-400'; bar.style.width = `${course.percentage}%`; track.append(bar); item.append(labels, gender, track); coursesContainer.append(item); });
            };

            const updateClock = () => { const now = new Date(); clock.textContent = now.toLocaleTimeString('es-DO', { hour12: false }); date.textContent = now.toLocaleDateString('es-DO', { day: '2-digit', month: 'short', year: 'numeric' }).replaceAll('.', '').toUpperCase(); };
            const refresh = async () => {
                try {
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' }); if (! response.ok) throw new Error('No fue posible actualizar'); const data = await response.json();
                    Object.entries(data.summary).forEach(([key, value]) => { document.querySelectorAll(`[data-summary="${key}"]`).forEach((element) => { element.textContent = key === 'percentage' ? `${value}%` : value; }); });
                    renderRecords(data.records); renderCourses(data.courses); updatedAt.textContent = data.generated_at; status.className = 'mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-emerald-300'; status.querySelector('span:last-child').textContent = 'Sistema activo';
                } catch (_) { status.className = 'mb-1 inline-flex items-center gap-2 rounded-full bg-amber-400/10 px-3 py-2 text-xs font-black uppercase tracking-wider text-amber-300'; status.querySelector('span:last-child').textContent = 'Reconectando'; }
            };
            updateClock(); window.setInterval(updateClock, 1000); window.setInterval(refresh, 5000);
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Panel docente | {{ $teacher->school?->name ?? 'Portal escolar' }}</title>
    <script>
        (() => {
            const theme = localStorage.getItem('school-panel-theme');
            document.documentElement.classList.toggle('dark', theme === 'dark' || (! theme && window.matchMedia('(prefers-color-scheme: dark)').matches));
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-stone-50 text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
        <aside class="hidden border-r border-teal-900 bg-teal-950 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
            <div class="border-b border-white/10 p-6">
                <a class="flex items-center gap-3" href="{{ route('dashboard.docente') }}"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-300 text-teal-950"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15z"/></svg></span><span><strong class="block">Aula activa</strong><small class="text-[10px] font-bold uppercase tracking-[.2em] text-teal-300">Panel docente</small></span></a>
            </div>
            <nav class="flex-1 space-y-2 p-4 text-sm font-bold text-teal-100/70">
                <a class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 text-white" href="#today">Mi clase de hoy</a>
                <a class="flex items-center gap-3 rounded-xl px-4 py-3 transition hover:bg-white/10 hover:text-white" href="#roster">Verificar estudiantes</a>
                <a class="flex items-center gap-3 rounded-xl px-4 py-3 transition hover:bg-white/10 hover:text-white" href="#reports">Incidencias reportadas</a>
            </nav>
            <div class="p-4"><div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-xs font-black uppercase tracking-wider text-teal-300">Regla del sistema</p><p class="mt-2 text-xs leading-5 text-teal-100/70">La huella confirma el plantel. Tú confirmas la presencia dentro del aula.</p></div></div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 border-b border-stone-200/80 bg-stone-50/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90">
                <div class="flex h-20 items-center justify-between gap-3 px-4 sm:px-8">
                    <div class="min-w-0"><p class="truncate text-sm font-black">{{ $teacher->school?->name ?? 'Centro educativo' }}</p><p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $teacher->name }} · Docente</p></div>
                    <div class="flex items-center gap-2"><button data-theme-toggle class="flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" type="button" aria-label="Cambiar tema"><svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.8A8.5 8.5 0 1111.2 3 6.7 6.7 0 0021 12.8z"/></svg><svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2m0 14v2m9-9h-2M5 12H3m13-5l1-1M7 17l-1 1m12 0l-1-1M7 7L6 6m10 6a4 4 0 11-8 0 4 4 0 018 0z"/></svg></button><form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-xs font-bold dark:border-slate-700 dark:bg-slate-900" type="submit">Salir</button></form></div>
                </div>
            </header>

            <main class="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-8">
                @if (session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>@endif
                @if ($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"><p class="font-black">No se pudo guardar:</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <section id="today" class="overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-cyan-900 p-5 text-white shadow-xl shadow-teal-950/10 sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1fr_auto] xl:items-end">
                        <div><span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-black uppercase tracking-[.16em] text-teal-100">Control por asignatura</span><h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">¿Quién está realmente en tu clase?</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-teal-100/75">Compara el ponche de entrada al plantel con tu verificación dentro del aula y registra cualquier incidencia.</p></div>
                        <form class="grid gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 sm:grid-cols-[minmax(12rem,1fr)_auto_auto]" action="{{ route('dashboard.docente') }}" method="GET"><label class="text-xs font-bold text-teal-100">Curso<select class="mt-1.5 w-full rounded-xl border-white/10 bg-teal-950 px-3 py-2.5 text-sm text-white" name="course">@forelse ($courses as $course)<option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->name }}</option>@empty<option>Sin cursos asignados</option>@endforelse</select></label><label class="text-xs font-bold text-teal-100">Fecha<input class="mt-1.5 w-full rounded-xl border-white/10 bg-teal-950 px-3 py-2.5 text-sm text-white" type="date" name="date" value="{{ $selectedDate->toDateString() }}"></label><button class="self-end rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-black text-teal-950" type="submit">Consultar</button></form>
                    </div>
                </section>

                @if ($selectedCourse)
                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ([['Estudiantes', $summary['students'], 'text-slate-900 dark:text-white'], ['En el plantel', $summary['on_campus'], 'text-emerald-600 dark:text-emerald-300'], ['Verificados', $summary['verified'], 'text-sky-600 dark:text-sky-300'], ['Pendientes', $summary['pending'], 'text-amber-600 dark:text-amber-300'], ['Plantel, no clase', $summary['campus_missing_class'], 'text-rose-600 dark:text-rose-300']] as [$label, $value, $color])<article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black {{ $color }}">{{ $value }}</p></article>@endforeach
                    </section>

                    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"><div><p class="text-xs font-black uppercase tracking-[.16em] text-teal-700 dark:text-teal-300">{{ $selectedCourse->code }}</p><h2 class="mt-1 text-xl font-black">{{ $selectedCourse->name }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $classSession?->subject ?: 'Verificación de asistencia' }} · {{ $selectedDate->locale('es')->isoFormat('dddd D [de] MMMM') }}</p></div>@if (! $classSession || $classSession->status === 'closed')<form class="flex flex-col gap-2 sm:flex-row" action="{{ route('teacher.sessions.start') }}" method="POST">@csrf<input type="hidden" name="course_id" value="{{ $selectedCourse->id }}"><input class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="subject" placeholder="Asignatura (opcional)"><button class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-black text-white" type="submit">Iniciar verificación</button></form>@else<form action="{{ route('teacher.sessions.close', $classSession) }}" method="POST">@csrf<button class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-black text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300" type="submit">Cerrar y guardar reporte</button></form>@endif</div>
                    </section>

                    <section id="roster" class="space-y-4">
                        <div><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Lista del curso</p><h2 class="mt-1 text-2xl font-black">Verificación estudiante por estudiante</h2></div>
                        @forelse ($roster as $row)
                            @php
                                $student = $row['student'];
                                $verification = $row['verification'];
                                $campusMeta = match ($row['campus_status']) {
                                    'on_campus' => ['En el plantel', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300'],
                                    'left_campus' => ['Salió del plantel', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
                                    default => ['Sin ponche', 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300'],
                                };
                            @endphp
                            <article class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                                <div class="grid gap-4 xl:grid-cols-[minmax(15rem,.8fr)_minmax(13rem,.6fr)_minmax(22rem,1.4fr)] xl:items-start">
                                    <div class="flex items-center gap-3"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-100 font-black text-teal-700 dark:bg-teal-400/15 dark:text-teal-300">{{ strtoupper(substr($student->nombre, 0, 1).substr($student->apellido, 0, 1)) }}</span><div class="min-w-0"><h3 class="truncate font-black">{{ $student->nombre }} {{ $student->apellido }}</h3><p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $student->matricula }} · ID {{ $student->id_lector }}</p></div></div>
                                    <div><span class="inline-flex rounded-full px-3 py-1.5 text-xs font-black {{ $campusMeta[1] }}">{{ $campusMeta[0] }}</span>@if ($row['campus_time'])<p class="mt-2 text-xs text-slate-500">Último registro: {{ $row['campus_time']->format('H:i') }}</p>@endif @if ($verification)<p class="mt-2 text-xs font-bold text-teal-700 dark:text-teal-300">{{ $statusLabels[$verification->status] }}</p>@endif</div>
                                    @if ($classSession && $classSession->status === 'open')
                                        <form class="grid gap-2 sm:grid-cols-[minmax(11rem,.8fr)_minmax(12rem,1fr)_auto]" action="{{ route('teacher.verifications.store') }}" method="POST">@csrf<input type="hidden" name="class_session_id" value="{{ $classSession->id }}"><input type="hidden" name="student_id" value="{{ $student->id }}"><select class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="status" required><option value="">Seleccionar estado</option>@foreach ($statusLabels as $status => $label)<option value="{{ $status }}" @selected($verification?->status === $status)>{{ $label }}</option>@endforeach</select><input class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="note" value="{{ $verification?->note }}" placeholder="Observación o reporte"><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white dark:bg-teal-600" type="submit">Guardar</button></form>
                                    @else
                                        <div class="rounded-xl bg-stone-100 px-4 py-3 text-sm text-slate-500 dark:bg-slate-800 dark:text-slate-400">Inicia la verificación para marcar la asistencia de esta clase.</div>
                                    @endif
                                </div>
                            </article>
                        @empty<div class="rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">No hay estudiantes vinculados a este curso.</div>@endforelse
                    </section>

                    <section id="reports" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="border-b border-stone-100 p-5 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-[.18em] text-rose-600 dark:text-rose-300">Seguimiento</p><h2 class="mt-1 text-xl font-black">Estudiantes en el plantel que faltaron a clase</h2></div><div class="divide-y divide-stone-100 dark:divide-slate-800">@forelse ($recentReports as $report)<div class="flex flex-col gap-2 p-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-black">{{ $report->student?->nombre }} {{ $report->student?->apellido }}</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $report->classSession?->course?->name }} · {{ $report->verified_at->format('d/m/Y H:i') }}</p>@if ($report->note)<p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $report->note }}</p>@endif</div><span class="w-fit rounded-full bg-rose-100 px-3 py-1.5 text-xs font-black text-rose-700 dark:bg-rose-400/15 dark:text-rose-300">Incidencia de aula</span></div>@empty<div class="p-10 text-center text-sm text-slate-500">Todavía no hay incidencias de este tipo.</div>@endforelse</div></section>
                @else
                    <section class="rounded-3xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-900"><h2 class="text-xl font-black">Aún no tienes cursos asignados</h2><p class="mt-2 text-sm text-slate-500">El superadministrador debe vincular al menos un curso a tu cuenta docente.</p></section>
                @endif
            </main>
        </div>
    </div>
</body>
</html>

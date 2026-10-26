<section id="students" class="space-y-5" data-students-console>
    <header class="flex flex-col gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
        <div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-500">{{ $activeSchool?->name }}</p><h1 class="mt-1 text-2xl font-black tracking-tight">Estudiantes</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestión de estudiantes, fotografías y perfiles biométricos.</p></div>
        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-black text-white hover:bg-teal-500" type="button" data-open-dialog="register-student-dialog"><span class="text-lg leading-none">+</span>Registrar estudiante</button>
    </header>

    <section class="grid grid-cols-2 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm lg:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
        @foreach ([['Estudiantes', $studentSummary['total'], 'text-sky-500'], ['Activos', $studentSummary['active'], 'text-emerald-500'], ['Con biometría', $studentSummary['biometric'], 'text-teal-500'], ['Pendientes', $studentSummary['pending'], 'text-amber-500']] as [$label, $value, $color])
            <article class="border-stone-200 px-4 py-4 even:border-l lg:border-l lg:first:border-l-0 dark:border-slate-800"><p class="text-xs font-bold text-slate-500">{{ $label }}</p><p class="mt-1 text-3xl font-black {{ $color }}">{{ number_format($value) }}</p></article>
        @endforeach
    </section>

    <form class="grid gap-3 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm md:grid-cols-[minmax(260px,1fr)_220px_180px_190px_auto] dark:border-slate-800 dark:bg-slate-900" method="GET" action="{{ route($dashboardPageRoute, 'students') }}">
        <label class="relative"><span class="sr-only">Buscar estudiantes</span><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg><input class="w-full rounded-xl border border-stone-200 bg-stone-50 py-2.5 pl-10 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950" type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Nombre, matrícula o ID biométrico…"></label>
        <select class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="student_course_id"><option value="">Todos los cursos</option>@foreach ($allCourses->where('is_active', true) as $course)<option value="{{ $course->id }}" @selected((int) request('student_course_id') === $course->id)>{{ $course->name }}</option>@endforeach</select>
        <select class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="student_status"><option value="">Todos los estados</option><option value="active" @selected(request('student_status') === 'active')>Activos</option><option value="inactive" @selected(request('student_status') === 'inactive')>Inactivos</option></select>
        <select class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" name="student_biometric"><option value="">Toda biometría</option><option value="registered" @selected(request('student_biometric') === 'registered')>Registrada</option><option value="pending" @selected(request('student_biometric') === 'pending')>Pendiente</option></select>
        <div class="flex gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white dark:bg-teal-600" type="submit">Filtrar</button>@if (request()->hasAny(['student_search', 'student_course_id', 'student_status', 'student_biometric']))<a class="rounded-xl px-3 py-2.5 text-sm font-bold text-slate-500 ring-1 ring-inset ring-stone-200 dark:ring-slate-700" href="{{ route($dashboardPageRoute, 'students') }}">Limpiar</a>@endif</div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto"><table class="w-full min-w-[860px] text-left"><thead class="bg-stone-50 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:bg-slate-950"><tr><th class="px-5 py-3">Estudiante</th><th class="px-5 py-3">Matrícula</th><th class="px-5 py-3">Curso</th><th class="px-5 py-3">Biometría</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead><tbody class="divide-y divide-stone-100 dark:divide-slate-800">
            @forelse ($students as $student)
                <tr class="hover:bg-stone-50/70 dark:hover:bg-slate-800/40"><td class="px-5 py-4"><div class="flex items-center gap-3">@if ($student->photo_path)<img class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white dark:ring-slate-800" src="{{ $student->photoUrl() }}" alt="Foto de {{ $student->nombre }} {{ $student->apellido }}">@else<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-black text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">{{ strtoupper(substr($student->nombre, 0, 1).substr($student->apellido, 0, 1)) }}</span>@endif<div><p class="text-sm font-black">{{ $student->nombre }} {{ $student->apellido }}</p><p class="mt-0.5 text-[11px] text-slate-400">N.º de lista {{ $student->numero_lista ?? '—' }}</p></div></div></td><td class="px-5 py-4 font-mono text-xs font-bold">{{ $student->matricula }}</td><td class="px-5 py-4 text-sm font-semibold">{{ $student->curso }}</td><td class="px-5 py-4">@if ($student->id_lector)<span class="inline-flex items-center gap-2 text-xs font-black text-emerald-600 dark:text-emerald-300"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Registrada</span><p class="mt-1 font-mono text-[10px] text-slate-400">ID {{ $student->id_lector }}</p>@else<span class="text-xs font-black text-amber-600">Pendiente</span>@endif</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $student->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800' }}">{{ $student->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><button class="rounded-lg px-3 py-2 text-xs font-black text-teal-600 ring-1 ring-inset ring-teal-200 dark:text-teal-300 dark:ring-teal-900" type="button" data-open-dialog="student-detail-{{ $student->id }}">Ver / editar</button><form action="{{ route('students.destroy', $student) }}" method="POST" data-confirm="¿Desactivar a {{ $student->nombre }} {{ $student->apellido }}?">@csrf @method('DELETE')<button class="rounded-lg px-3 py-2 text-xs font-bold text-rose-600" type="submit">Desactivar</button></form></div></td></tr>
            @empty
                <tr><td class="px-6 py-16 text-center text-sm text-slate-500" colspan="6">No hay estudiantes que coincidan con los filtros.</td></tr>
            @endforelse
        </tbody></table></div>
        @if ($students->hasPages())<div class="border-t border-stone-100 px-5 py-4 dark:border-slate-800">{{ $students->links() }}</div>@endif
    </article>

    <dialog id="register-student-dialog" class="m-0 ml-auto h-full max-h-none w-full max-w-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/75 dark:bg-slate-900 dark:text-slate-100">
        <x-student-form :courses="$allCourses->where('is_active', true)" :school="$activeSchool" />
    </dialog>

    @foreach ($students as $student)
        <dialog id="student-detail-{{ $student->id }}" class="m-0 ml-auto h-full max-h-none w-full max-w-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/75 dark:bg-slate-900 dark:text-slate-100">
            <x-student-form :courses="$allCourses" :student="$student" :school="$activeSchool" />
        </dialog>
    @endforeach
</section>

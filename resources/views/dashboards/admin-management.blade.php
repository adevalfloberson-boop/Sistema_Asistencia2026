@php
    $fieldClass = 'w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 dark:border-slate-700 dark:bg-slate-950';
    $cardClass = 'scroll-mt-32 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';
@endphp

<section id="students" class="{{ $cardClass }}">
    <div class="border-b border-stone-100 px-5 py-5 sm:px-6 dark:border-slate-800">
        <p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Comunidad educativa</p>
        <h2 class="mt-1 text-xl font-black">Registrar estudiante</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Matrícula, número de lista, curso y vínculo biométrico en un solo perfil.</p>
    </div>
    <form class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 sm:p-6" action="{{ route('students.store') }}" method="POST">
        @csrf
        <label class="text-sm font-bold"><span class="mb-2 block">Escuela</span><select class="{{ $fieldClass }}" name="school_id" required>@foreach ($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label>
        <label class="text-sm font-bold"><span class="mb-2 block">Curso</span><select class="{{ $fieldClass }}" name="course_id" required><option value="">Seleccionar</option>@foreach ($allCourses->where('is_active', true) as $course)<option value="{{ $course->id }}">{{ $course->school?->short_name ?? $course->school?->code }} · {{ $course->name }}</option>@endforeach</select></label>
        @foreach ([['matricula', 'Matrícula', '2026-001'], ['numero_lista', 'N.º de lista', '1'], ['nombre', 'Nombre', 'Nombre'], ['apellido', 'Apellido', 'Apellido'], ['id_lector', 'ID biométrico', '1001']] as [$name, $label, $placeholder])
            <label class="text-sm font-bold"><span class="mb-2 block">{{ $label }}</span><input class="{{ $fieldClass }}" name="{{ $name }}" {{ $name === 'numero_lista' ? 'type=number min=1 max=999' : '' }} required value="{{ old($name) }}" placeholder="{{ $placeholder }}"></label>
        @endforeach
        <div class="flex items-end"><button class="w-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-black text-white hover:bg-slate-700 dark:bg-amber-300 dark:text-teal-950" type="submit">Guardar estudiante</button></div>
    </form>
    <div class="overflow-x-auto border-t border-stone-100 dark:border-slate-800">
        <table class="w-full min-w-[920px] text-left text-sm"><thead class="bg-stone-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-950"><tr><th class="px-5 py-3">Lista</th><th class="px-5 py-3">Estudiante</th><th class="px-5 py-3">Matrícula</th><th class="px-5 py-3">Área / curso</th><th class="px-5 py-3">ID lector</th><th class="px-5 py-3">Acciones</th></tr></thead>
        <tbody class="divide-y divide-stone-100 dark:divide-slate-800">@forelse ($students as $student)<tr><td class="px-5 py-4 font-black">{{ $student->numero_lista ?? '—' }}</td><td class="px-5 py-4 font-bold">{{ $student->nombre }} {{ $student->apellido }}</td><td class="px-5 py-4 font-mono text-xs">{{ $student->matricula }}</td><td class="px-5 py-4">{{ $student->area ?: 'General' }} · {{ $student->curso }}{{ $student->seccion ? ' / '.$student->seccion : '' }}</td><td class="px-5 py-4 font-mono">{{ $student->id_lector }}</td><td class="px-5 py-4"><details><summary class="cursor-pointer font-black text-teal-700 dark:text-teal-300">Editar</summary><form class="mt-3 grid w-[34rem] grid-cols-2 gap-2" action="{{ route('students.update', $student) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="school_id" value="{{ $student->school_id }}"><select class="{{ $fieldClass }}" name="course_id">@foreach ($allCourses->where('school_id', $student->school_id) as $course)<option value="{{ $course->id }}" @selected($student->course_id === $course->id)>{{ $course->name }}</option>@endforeach</select><input class="{{ $fieldClass }}" name="matricula" value="{{ $student->matricula }}"><input class="{{ $fieldClass }}" name="numero_lista" type="number" min="1" max="999" value="{{ $student->numero_lista }}"><input class="{{ $fieldClass }}" name="nombre" value="{{ $student->nombre }}"><input class="{{ $fieldClass }}" name="apellido" value="{{ $student->apellido }}"><input class="{{ $fieldClass }}" name="id_lector" value="{{ $student->id_lector }}"><button class="rounded-xl bg-teal-700 px-4 py-2 font-black text-white" type="submit">Actualizar</button></form><form class="mt-2" action="{{ route('students.destroy', $student) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-black text-rose-600" type="submit">Desactivar estudiante</button></form></details></td></tr>@empty<tr><td class="px-5 py-10 text-center text-slate-500" colspan="6">No hay estudiantes registrados.</td></tr>@endforelse</tbody></table>
    </div>
</section>

<section id="courses" class="{{ $cardClass }}">
    <div class="border-b border-stone-100 px-5 py-5 sm:px-6 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Organización académica</p><h2 class="mt-1 text-xl font-black">Cursos, áreas y secciones</h2></div>
    <form class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 sm:p-6" action="{{ route('courses.store') }}" method="POST">@csrf
        <label class="text-sm font-bold"><span class="mb-2 block">Escuela</span><select class="{{ $fieldClass }}" name="school_id" required>@foreach ($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label>
        @foreach ([['code', 'Código', '1A-2026'], ['name', 'Nombre', '1.º A'], ['grade', 'Grado', 'Primero'], ['area', 'Área', 'Primaria'], ['section', 'Sección', 'A'], ['shift', 'Tanda', 'Matutina']] as [$name, $label, $placeholder])<label class="text-sm font-bold"><span class="mb-2 block">{{ $label }}</span><input class="{{ $fieldClass }}" name="{{ $name }}" {{ in_array($name, ['code', 'name'], true) ? 'required' : '' }} placeholder="{{ $placeholder }}"></label>@endforeach
        <div class="flex items-end"><button class="w-full rounded-xl bg-teal-700 px-5 py-3 text-sm font-black text-white" type="submit">Crear curso</button></div>
    </form>
    <div class="grid gap-3 border-t border-stone-100 p-5 md:grid-cols-2 xl:grid-cols-3 dark:border-slate-800">@forelse ($allCourses as $course)<article class="rounded-xl border border-stone-200 p-4 dark:border-slate-700"><div class="flex justify-between gap-3"><div><p class="font-black">{{ $course->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $course->school?->name }} · {{ $course->students_count }} alumnos</p></div><span class="text-xs font-bold {{ $course->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $course->is_active ? 'Activo' : 'Inactivo' }}</span></div><p class="mt-3 text-sm">{{ $course->area ?: 'Área general' }} · {{ $course->grade ?: 'Sin grado' }} · Sección {{ $course->section ?: '—' }} · {{ $course->shift ?: 'Sin tanda' }}</p><details class="mt-3"><summary class="cursor-pointer text-xs font-black text-teal-700 dark:text-teal-300">Editar curso</summary><form class="mt-3 grid grid-cols-2 gap-2" action="{{ route('courses.update', $course) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="school_id" value="{{ $course->school_id }}">@foreach (['code', 'name', 'grade', 'area', 'section', 'shift'] as $name)<input class="{{ $fieldClass }}" name="{{ $name }}" value="{{ $course->{$name} }}" placeholder="{{ ucfirst($name) }}"></input>@endforeach<button class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-black text-white" type="submit">Guardar</button></form>@if ($course->is_active)<form class="mt-2" action="{{ route('courses.destroy', $course) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-black text-rose-600" type="submit">Desactivar curso</button></form>@endif</details></article>@empty<p class="text-sm text-slate-500">No hay cursos registrados.</p>@endforelse</div>
</section>

<section id="teachers" class="{{ $cardClass }}">
    <div class="border-b border-stone-100 px-5 py-5 sm:px-6 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Personal docente</p><h2 class="mt-1 text-xl font-black">Docentes y cursos asignados</h2></div>
    <form class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 sm:p-6" action="{{ route('teachers.store') }}" method="POST">@csrf
        <label class="text-sm font-bold"><span class="mb-2 block">Escuela</span><select class="{{ $fieldClass }}" name="school_id" required>@foreach ($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label>
        @foreach ([['name', 'Nombre completo', 'Docente'], ['username', 'Usuario', 'docente01'], ['email', 'Correo', 'docente@escuela.edu'], ['password', 'Contraseña', '10 caracteres mínimo'], ['password_confirmation', 'Confirmar contraseña', 'Repetir contraseña']] as [$name, $label, $placeholder])<label class="text-sm font-bold"><span class="mb-2 block">{{ $label }}</span><input class="{{ $fieldClass }}" name="{{ $name }}" type="{{ str_contains($name, 'password') ? 'password' : ($name === 'email' ? 'email' : 'text') }}" required placeholder="{{ $placeholder }}"></label>@endforeach
        <label class="text-sm font-bold sm:col-span-2"><span class="mb-2 block">Cursos asignados</span><select class="{{ $fieldClass }}" name="course_ids[]" required multiple size="4">@foreach ($allCourses->where('is_active', true) as $course)<option value="{{ $course->id }}">{{ $course->school?->code }} · {{ $course->name }}</option>@endforeach</select></label>
        <div class="flex items-end"><button class="w-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-black text-white dark:bg-amber-300 dark:text-teal-950" type="submit">Crear docente</button></div>
    </form>
    <div class="grid gap-3 border-t border-stone-100 p-5 md:grid-cols-2 xl:grid-cols-3 dark:border-slate-800">
        @forelse ($teachers as $teacher)
            <article class="rounded-xl border border-stone-200 p-4 dark:border-slate-700">
                <div class="flex justify-between"><p class="font-black">{{ $teacher->name }}</p><span class="text-xs font-bold {{ $teacher->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $teacher->is_active ? 'Activo' : 'Inactivo' }}</span></div>
                <p class="mt-1 text-xs text-slate-500">{{ $teacher->username }} · {{ $teacher->email }}</p>
                <p class="mt-3 text-sm font-semibold">{{ $teacher->courses->pluck('name')->join(', ') ?: 'Sin cursos' }}</p>
                <details class="mt-3">
                    <summary class="cursor-pointer text-xs font-black text-teal-700 dark:text-teal-300">Editar docente</summary>
                    <form class="mt-3 grid grid-cols-2 gap-2" action="{{ route('teachers.update', $teacher) }}" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" name="school_id" value="{{ $teacher->school_id }}">
                        <input class="{{ $fieldClass }}" name="name" value="{{ $teacher->name }}" required>
                        <input class="{{ $fieldClass }}" name="username" value="{{ $teacher->username }}" required>
                        <input class="{{ $fieldClass }} col-span-2" name="email" type="email" value="{{ $teacher->email }}" required>
                        <input class="{{ $fieldClass }}" name="password" type="password" placeholder="Nueva clave (opcional)">
                        <input class="{{ $fieldClass }}" name="password_confirmation" type="password" placeholder="Confirmar nueva clave">
                        <select class="{{ $fieldClass }} col-span-2" name="course_ids[]" required multiple size="4">@foreach ($allCourses->where('school_id', $teacher->school_id)->where('is_active', true) as $course)<option value="{{ $course->id }}" @selected($teacher->courses->contains($course))>{{ $course->name }}</option>@endforeach</select>
                        <button class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-black text-white" type="submit">Guardar docente</button>
                    </form>
                </details>
                @if ($teacher->is_active)<form class="mt-3" action="{{ route('teachers.destroy', $teacher) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-black text-rose-600" type="submit">Desactivar docente</button></form>@endif
            </article>
        @empty
            <p class="text-sm text-slate-500">No hay docentes registrados.</p>
        @endforelse
    </div>
</section>

<section id="settings" class="{{ $cardClass }}">
    <div class="border-b border-stone-100 px-5 py-5 sm:px-6 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-[.18em] text-teal-700 dark:text-teal-300">Reglas del plantel</p><h2 class="mt-1 text-xl font-black">Control de Entrada y Salida</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Los eventos dentro del intervalo se sincronizan, pero no cambian el estado del alumno.</p></div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">@foreach ($schools as $school)<form class="rounded-xl border border-stone-200 p-4 dark:border-slate-700" action="{{ route('schools.settings.update', $school) }}" method="POST">@csrf @method('PUT')<p class="font-black">{{ $school->name }}</p><label class="mt-4 block text-sm font-bold"><span class="mb-2 block">Minutos mínimos entre ponches</span><input class="{{ $fieldClass }}" name="attendance_cooldown_minutes" type="number" min="1" max="120" required value="{{ $school->attendance_cooldown_minutes }}"></label><button class="mt-3 rounded-xl bg-teal-700 px-4 py-2 text-sm font-black text-white" type="submit">Guardar regla</button></form>@endforeach</div>
</section>

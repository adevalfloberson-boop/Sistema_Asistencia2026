@extends('antigravity.layouts.main')

@section('title', 'Directorio de Estudiantes | ' . ($school->name ?? 'Portal Escolar'))

@section('content')
<div class="min-h-screen">
    {{-- TOPBAR DIRECTIVA --}}
    <header class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-slate-950 font-black text-sm shadow-md shadow-emerald-500/15">
                    {{ strtoupper(substr($school->short_name ?? 'SP', 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 dark:text-white leading-tight">
                        {{ $school->name ?? 'Colegio Bilingüe San Patricio' }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Directorio Académico y Biométrico</p>
                </div>
            </div>

            <button onclick="document.getElementById('modal-student').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-slate-950 font-bold text-xs uppercase tracking-wider shadow-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nuevo Estudiante</span>
            </button>
        </div>

        {{-- SUB-NAVEGACIÓN INTERNA DEL ADMIN --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-2 overflow-x-auto border-t border-slate-100 dark:border-slate-800/60 py-2.5 text-xs font-bold">
            <a href="{{ route('antigravity.admin') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Resumen General
            </a>
            <a href="{{ route('antigravity.admin.students') }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80">
                Estudiantes y Cursos
            </a>
            <a href="{{ route('antigravity.admin.enrollment') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Estación de Huellas (10 Dedos)
            </a>
            <a href="{{ route('antigravity.admin.devices') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Radar de Lectores Biométricos
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- FILTROS Y BUSCADOR --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <button class="px-3.5 py-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-950 text-xs font-bold">Todos ({{ $students->count() }})</button>
                @foreach ($courses as $c)
                <button class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-slate-600 dark:text-slate-300 text-xs font-bold transition whitespace-nowrap">
                    {{ $c->name }}
                </button>
                @endforeach
            </div>

            <div class="relative w-full sm:w-72">
                <input type="text" id="search-input" placeholder="Buscar por nombre, matrícula..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-medium placeholder-slate-400 focus:border-emerald-500 focus:outline-none transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        {{-- TABLA ESTILO COMERCIAL ELEVADA --}}
        <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-4">N.º Lista</th>
                            <th class="px-6 py-4">Estudiante</th>
                            <th class="px-6 py-4">Matrícula</th>
                            <th class="px-6 py-4">Curso / Área</th>
                            <th class="px-6 py-4">ID Lector Biométrico</th>
                            <th class="px-6 py-4">Estado Huella</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($students as $st)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-400">{{ sprintf('%02d', $st->numero_lista ?? 1) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-extrabold text-xs flex items-center justify-center">
                                        {{ strtoupper(substr($st->nombre, 0, 1) . substr($st->apellido, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 dark:text-white leading-tight">{{ $st->nombre }} {{ $st->apellido }}</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $st->curso }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $st->matricula }}</td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-600 dark:text-slate-400">{{ $st->area ?: 'Secundaria' }} · Sección {{ $st->seccion ?: 'A' }}</td>
                            <td class="px-6 py-4 font-mono text-xs font-black text-slate-900 dark:text-white">{{ $st->id_lector }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Enrolada (Índice)
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('antigravity.admin.enrollment') }}" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-xs font-bold text-emerald-600 dark:text-emerald-400 transition">
                                    Ver Huella
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- MODAL SIMULADO DE REGISTRO --}}
    <div id="modal-student" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Registrar Nuevo Estudiante</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Asignación de matrícula e ID para lector ZKTeco</p>
                </div>
                <button onclick="document.getElementById('modal-student').classList.add('hidden')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    ✕
                </button>
            </div>

            <form onsubmit="event.preventDefault(); alert('Estudiante simulado guardado correctamente con sincronización ADMS.'); document.getElementById('modal-student').classList.add('hidden')" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
                        <input type="text" value="Daniel" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Apellido</label>
                        <input type="text" value="Guerrero Peña" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Curso</label>
                        <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium">
                            @foreach ($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">N.º de Lista</label>
                        <input type="number" value="11" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Matrícula</label>
                        <input type="text" value="MAT-2026-035" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ID Biométrico</label>
                        <input type="text" value="1035" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-mono font-bold">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('modal-student').classList.add('hidden')" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="flex-1 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold uppercase tracking-wider transition">Guardar y Enrolar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

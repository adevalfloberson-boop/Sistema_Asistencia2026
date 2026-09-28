@extends('antigravity.layouts.main')

@section('title', 'Aula Activa Docente | ' . ($school->name ?? 'Portal Escolar'))

@section('content')
<div class="min-h-screen bg-slate-100 dark:bg-slate-950">
    {{-- TOPBAR DOCENTE --}}
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-500 flex items-center justify-center text-white font-black text-sm shadow-md shadow-violet-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <h1 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                        Aula Activa · {{ $school->name ?? 'San Patricio' }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {{ $docente->name ?? 'Prof. Carlos Ramírez' }} · Docente de Matemáticas
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="autoReconcileAll()" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 text-xs font-black uppercase tracking-wider hover:bg-emerald-100 transition shadow-xs">
                    <span>⚡ Reconciliar Todo con Puerta</span>
                </button>

                <button onclick="alert('Sesión de clase guardada y reporte enviado a Dirección Académica.')" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-950 font-extrabold text-xs uppercase tracking-wider shadow-md transition">
                    Finalizar Clase
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- HEADER DE CLASE Y RECONCILIACIÓN EN TIEMPO REAL --}}
        <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl shadow-indigo-950/20 border border-indigo-900/40">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-black uppercase tracking-wider border border-indigo-400/20">
                            1.º Secundaria A
                        </span>
                        <span class="text-xs text-indigo-300/70">Período 1 (08:00 - 08:45 AM)</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight">Matemáticas y Razonamiento Lógico</h2>
                    <p class="text-xs sm:text-sm text-indigo-200/70 mt-1 max-w-xl">
                        Pase de lista con Reconciliación Inteligente: el sistema cruza la huella de entrada en los torniquetes con la presencia dentro del aula.
                    </p>
                </div>

                {{-- STATS DE CLASE --}}
                <div class="flex items-center gap-3">
                    <div class="bg-white/10 backdrop-blur-md px-5 py-3.5 rounded-2xl border border-white/10 text-center">
                        <p class="text-[10px] text-indigo-200 font-extrabold uppercase tracking-wider">En el Colegio</p>
                        <p class="text-2xl font-black text-emerald-400 mt-0.5">8 <span class="text-xs text-slate-300 font-normal">/ 10</span></p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md px-5 py-3.5 rounded-2xl border border-white/10 text-center">
                        <p class="text-[10px] text-indigo-200 font-extrabold uppercase tracking-wider">En Aula</p>
                        <p id="counter-present" class="text-2xl font-black text-white mt-0.5">7 <span class="text-xs text-slate-300 font-normal">/ 10</span></p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md px-5 py-3.5 rounded-2xl border border-white/10 text-center">
                        <p class="text-[10px] text-indigo-200 font-extrabold uppercase tracking-wider">Tardanzas</p>
                        <p id="counter-late" class="text-2xl font-black text-amber-400 mt-0.5">1</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTÓN RECONCILIAR PARA MÓVILES --}}
        <div class="sm:hidden">
            <button onclick="autoReconcileAll()" class="w-full py-3 rounded-2xl bg-emerald-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-md">
                ⚡ Reconciliar Todo con Huella Exterior
            </button>
        </div>

        {{-- LISTA TÁCTIL DE ALUMNOS --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-xs font-black uppercase tracking-[.18em] text-slate-400">Estudiantes del Curso (Toca para alternar)</h3>
                <span class="text-xs text-slate-400">10 inscritos</span>
            </div>

            @foreach ($students as $index => $st)
            @php
                $punch = $todayPunches->get($st->id);
                $hasPunched = $punch !== null;
            @endphp
            <div class="p-4 sm:p-5 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 transition hover:border-slate-300 dark:hover:border-slate-700">
                <div class="flex items-center gap-3.5">
                    <span class="font-mono font-black text-sm text-slate-400 w-7 text-center">{{ sprintf('%02d', $st->numero_lista ?? $index + 1) }}</span>
                    <div class="w-11 h-11 rounded-2xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-black text-xs flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($st->nombre, 0, 1) . substr($st->apellido, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-black text-sm text-slate-900 dark:text-white">{{ $st->nombre }} {{ $st->apellido }}</p>
                            @if ($index === 0)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Transporte demorado</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="font-mono text-xs text-slate-400 font-semibold">{{ $st->matricula }}</span>
                            <span class="text-slate-300 dark:text-slate-700">·</span>
                            @if ($hasPunched)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Ponchó a las {{ $punch->fecha_hora->format('07:i A') }} (Torniquetes)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded-md border border-rose-200 dark:border-rose-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    No ha ponchado en la entrada
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- BOTONES TÁCTILES ESTILO TABLET --}}
                <div class="flex items-center gap-1.5 self-end md:self-center" id="row-buttons-{{ $st->id }}" data-has-punch="{{ $hasPunched ? '1' : '0' }}">
                    <button type="button" onclick="setRollStatus({{ $st->id }}, 'present')" class="btn-st-{{ $st->id }} px-4 py-2.5 rounded-xl border text-xs font-black transition {{ $index !== 7 && $index !== 9 ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 shadow-xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400' }}">
                        ✓ Presente
                    </button>
                    <button type="button" onclick="setRollStatus({{ $st->id }}, 'late')" class="btn-st-{{ $st->id }} px-3.5 py-2.5 rounded-xl border text-xs font-black transition {{ $index === 0 ? 'border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300 shadow-xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400' }}">
                        ⏱ Tarde
                    </button>
                    <button type="button" onclick="setRollStatus({{ $st->id }}, 'absent')" class="btn-st-{{ $st->id }} px-3.5 py-2.5 rounded-xl border text-xs font-black transition {{ $index === 7 || $index === 9 ? 'border-rose-500 bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-300 shadow-xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400' }}">
                        ✕ Ausente
                    </button>
                    <button type="button" onclick="setRollStatus({{ $st->id }}, 'excused')" class="btn-st-{{ $st->id }} px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 text-xs font-black transition hover:border-sky-500">
                        Excusa
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </main>
</div>

<script>
    function setRollStatus(studentId, status) {
        const buttons = document.querySelectorAll('.btn-st-' + studentId);
        buttons.forEach(b => {
            b.className = 'btn-st-' + studentId + ' px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 text-xs font-black transition';
        });

        const activeBtn = event.currentTarget;
        if (status === 'present') {
            activeBtn.className = 'btn-st-' + studentId + ' px-4 py-2.5 rounded-xl border border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-black transition shadow-xs';
        } else if (status === 'late') {
            activeBtn.className = 'btn-st-' + studentId + ' px-3.5 py-2.5 rounded-xl border border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-xs font-black transition shadow-xs';
        } else if (status === 'absent') {
            activeBtn.className = 'btn-st-' + studentId + ' px-3.5 py-2.5 rounded-xl border border-rose-500 bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-300 text-xs font-black transition shadow-xs';
        } else if (status === 'excused') {
            activeBtn.className = 'btn-st-' + studentId + ' px-3 py-2.5 rounded-xl border border-sky-500 bg-sky-50 text-sky-800 dark:bg-sky-950 dark:text-sky-300 text-xs font-black transition shadow-xs';
        }
    }

    function autoReconcileAll() {
        document.querySelectorAll('[id^="row-buttons-"]').forEach(row => {
            const studentId = row.id.replace('row-buttons-', '');
            const hasPunch = row.getAttribute('data-has-punch') === '1';
            const buttons = row.querySelectorAll('button');

            buttons.forEach(b => {
                b.className = 'btn-st-' + studentId + ' px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 text-xs font-black transition';
            });

            if (hasPunch) {
                buttons[0].className = 'btn-st-' + studentId + ' px-4 py-2.5 rounded-xl border border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-black transition shadow-xs';
            } else {
                buttons[2].className = 'btn-st-' + studentId + ' px-3.5 py-2.5 rounded-xl border border-rose-500 bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-300 text-xs font-black transition shadow-xs';
            }
        });
        alert('Reconciliación automática aplicada: 8 estudiantes marcados presentes por ponche exterior y 2 ausentes.');
    }
</script>
@endsection

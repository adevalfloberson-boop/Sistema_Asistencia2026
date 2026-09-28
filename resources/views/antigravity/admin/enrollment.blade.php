@extends('antigravity.layouts.main')

@section('title', 'Estación de Enrolamiento Biométrico | ' . ($school->name ?? 'Portal Escolar'))

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
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Estación de Enrolamiento Biométrico ADMS</p>
                </div>
            </div>

            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-bold border border-emerald-300 dark:border-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Sensor Listo para Captura
            </span>
        </div>

        {{-- SUB-NAVEGACIÓN INTERNA DEL ADMIN --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-2 overflow-x-auto border-t border-slate-100 dark:border-slate-800/60 py-2.5 text-xs font-bold">
            <a href="{{ route('antigravity.admin') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Resumen General
            </a>
            <a href="{{ route('antigravity.admin.students') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Estudiantes y Cursos
            </a>
            <a href="{{ route('antigravity.admin.enrollment') }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80">
                Estación de Huellas (10 Dedos)
            </a>
            <a href="{{ route('antigravity.admin.devices') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Radar de Lectores Biométricos
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- PANEL PRINCIPAL DE CAPTURA --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {{-- COLUMNA IZQUIERDA: SELECCIÓN DE ESTUDIANTE Y DEDO (7 cols) --}}
            <div class="lg:col-span-7 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.18em] text-emerald-600 dark:text-emerald-400">Paso 1 y 2</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white mt-1">Selecciona el Estudiante y Dedo a Enrolar</h2>
                    <p class="text-xs text-slate-400 mt-1">Se recomienda registrar el dedo Índice derecho o Pulgar para mayor velocidad de lectura.</p>
                </div>

                {{-- SELECTOR DE ALUMNO --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Estudiante a Enrolar</label>
                    <select id="select-student" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm font-bold text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        @foreach ($students->take(10) as $st)
                        <option value="{{ $st->id }}">{{ $st->nombre }} {{ $st->apellido }} · {{ $st->curso }} (ID: {{ $st->id_lector }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- MAPA ANATÓMICO INTERACTIVO DE 10 DEDOS --}}
                <div class="space-y-3 pt-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Seleccionar Dedo (Haz clic en un dedo)</label>

                    <div class="p-6 rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 text-center">
                        <div class="grid grid-cols-2 gap-6">
                            {{-- MANO IZQUIERDA --}}
                            <div class="space-y-3">
                                <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Mano Izquierda</p>
                                <div class="flex justify-center gap-2">
                                    <button type="button" onclick="selectFinger(5, 'Meñique Izquierdo')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Meñique</button>
                                    <button type="button" onclick="selectFinger(4, 'Anular Izquierdo')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Anular</button>
                                    <button type="button" onclick="selectFinger(3, 'Medio Izquierdo')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Medio</button>
                                    <button type="button" onclick="selectFinger(2, 'Índice Izquierdo')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Índice</button>
                                    <button type="button" onclick="selectFinger(1, 'Pulgar Izquierdo')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Pulgar</button>
                                </div>
                            </div>

                            {{-- MANO DERECHA --}}
                            <div class="space-y-3">
                                <p class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Mano Derecha (Recomendada)</p>
                                <div class="flex justify-center gap-2">
                                    <button type="button" onclick="selectFinger(6, 'Pulgar Derecho')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Pulgar</button>
                                    <button type="button" id="btn-default-finger" onclick="selectFinger(7, 'Índice Derecho')" class="finger-btn p-2 rounded-xl border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-950 text-[10px] font-black text-emerald-700 dark:text-emerald-300 transition shadow-sm">Índice ★</button>
                                    <button type="button" onclick="selectFinger(8, 'Medio Derecho')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Medio</button>
                                    <button type="button" onclick="selectFinger(9, 'Anular Derecho')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Anular</button>
                                    <button type="button" onclick="selectFinger(10, 'Meñique Derecho')" class="finger-btn p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-emerald-500 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition">Meñique</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-200 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200">
                            Dedo Seleccionado: <span id="selected-finger-label" class="text-emerald-600 dark:text-emerald-400 font-extrabold">Índice Derecho (ID #7)</span>
                        </div>
                    </div>
                </div>

                {{-- DISPOSITIVO LECTOR --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Lector Biométrico para Enrolar</label>
                    <select class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm font-bold text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        @foreach ($devices as $dev)
                        <option value="{{ $dev->id }}">{{ $dev->name }} ({{ $dev->ip_address }}) · {{ $dev->location }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- COLUMNA DERECHA: SIMULADOR DE ESCANEO BIOMÉTRICO (5 cols) --}}
            <div class="lg:col-span-5 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm flex flex-col justify-between space-y-6">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.18em] text-emerald-600 dark:text-emerald-400">Paso 3</span>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mt-1">Escáner Biométrico</h3>
                    <p class="text-xs text-slate-400 mt-1">Calidad de plantilla y confirmación de huella.</p>
                </div>

                {{-- HUELLA DIGITAL ANIMADA --}}
                <div class="flex flex-col items-center justify-center p-8 rounded-3xl bg-slate-950 text-white border border-slate-800 relative overflow-hidden">
                    <div class="w-28 h-28 rounded-full border-2 border-dashed border-emerald-500/50 flex items-center justify-center relative">
                        <svg class="w-16 h-16 text-emerald-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/>
                        </svg>
                    </div>

                    <div class="mt-6 text-center">
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Estado del Sensor</p>
                        <p id="scan-status" class="text-sm font-black text-emerald-400 mt-0.5">Esperando dedo en el sensor</p>
                    </div>

                    <div class="mt-4 w-full max-w-xs bg-slate-800 h-2 rounded-full overflow-hidden">
                        <div id="scan-progress" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-700" style="width: 94%"></div>
                    </div>

                    <div class="mt-2 flex justify-between w-full max-w-xs text-[10px] font-bold text-slate-400">
                        <span>Calidad de Muestra</span>
                        <span class="text-emerald-400 font-black">94% (Óptima)</span>
                    </div>
                </div>

                {{-- BOTÓN DE INICIAR CAPTURA --}}
                <button type="button" onclick="simulateScan()" class="w-full py-4 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/25 transition">
                    Simular Captura en Lector
                </button>
            </div>
        </div>
    </main>
</div>

<script>
    function selectFinger(id, name) {
        document.querySelectorAll('.finger-btn').forEach(b => {
            b.classList.remove('border-2', 'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950', 'text-emerald-700', 'dark:text-emerald-300');
            b.classList.add('border', 'border-slate-300', 'dark:border-slate-700');
        });
        event.currentTarget.classList.add('border-2', 'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950', 'text-emerald-700', 'dark:text-emerald-300');
        document.getElementById('selected-finger-label').textContent = name + ' (ID #' + id + ')';
    }

    function simulateScan() {
        const status = document.getElementById('scan-status');
        const progress = document.getElementById('scan-progress');

        status.textContent = 'Coloque el dedo en el sensor...';
        progress.style.width = '20%';

        setTimeout(() => {
            status.textContent = 'Muestra 1 capturada. Levante el dedo...';
            progress.style.width = '60%';
            setTimeout(() => {
                status.textContent = 'Muestra 2 confirmada. ¡Huella enrolada con éxito!';
                progress.style.width = '100%';
            }, 900);
        }, 800);
    }
</script>
@endsection

@extends('antigravity.layouts.main')

@section('title', 'Kiosco de Asistencia en Vivo | ' . ($school->name ?? 'Portal Escolar'))

@section('content')
<div class="min-h-screen bg-slate-950 text-white p-6 sm:p-10 flex flex-col justify-between">
    {{-- ENCABEZADO DEL KIOSCO --}}
    <header class="flex items-center justify-between border-b border-slate-800/80 pb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg shadow-emerald-500/20">
                {{ strtoupper(substr($school->short_name ?? 'SP', 0, 2)) }}
            </div>
            <div>
                <span class="text-xs font-black uppercase tracking-[.25em] text-emerald-400">Recepción y Control de Acceso</span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $school->name ?? 'Colegio Bilingüe San Patricio' }}</h1>
            </div>
        </div>

        <div class="text-right">
            <p id="kiosk-clock" class="text-3xl sm:text-4xl font-mono font-black text-emerald-400 tracking-wider">--:--:--</p>
            <p id="kiosk-date" class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-1">Cargando fecha...</p>
        </div>
    </header>

    {{-- CUERPO CENTRAL: CONTADOR Y FEED EN VIVO --}}
    <main class="my-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- CONTADOR GIGANTE (1 Columna) --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 backdrop-blur-md p-8 flex flex-col justify-between">
            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-black uppercase tracking-wider mb-4 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Torniquetes Biométricos Activos
                </span>
                <h2 class="text-xl font-black text-slate-300">Total de Alumnos en el Plantel</h2>
                <p class="text-6xl sm:text-7xl font-black text-white mt-6 tracking-tight">
                    {{ $todayTotal }}
                </p>
                <p class="text-xs text-slate-400 mt-2">Ponches de entrada verificados hoy</p>
            </div>

            <div class="pt-6 border-t border-slate-800 text-xs text-slate-400">
                <p class="font-bold text-slate-300">Horario de Ingreso Escolar</p>
                <p class="mt-1">07:30 AM — 08:00 AM (Jornada Regular)</p>
            </div>
        </div>

        {{-- REGISTROS RECIENTES EN PANTALLA GIGANTE (2 Columnas) --}}
        <div class="lg:col-span-2 rounded-3xl border border-slate-800 bg-slate-900/70 backdrop-blur-md p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-black text-slate-200">Últimos Estudiantes que Ingresaron</h3>
                <span class="text-xs text-emerald-400 font-bold">Sincronización en vivo</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[460px] overflow-y-auto pr-2">
                @forelse ($recentPunches as $p)
                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 font-black text-sm flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($p->student?->nombre ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-white truncate">{{ $p->student?->nombre }} {{ $p->student?->apellido }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $p->curso }} · ID {{ $p->id_lector }}</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="font-mono text-sm font-black text-emerald-400 block">{{ $p->fecha_hora->format('h:i A') }}</span>
                        <span class="text-[10px] font-bold text-slate-500">Entrada</span>
                    </div>
                </div>
                @empty
                <p class="col-span-2 text-center text-slate-500 py-12">No hay ponches recientes para mostrar.</p>
                @endforelse
            </div>
        </div>
    </main>

    {{-- FOOTER DEL KIOSCO --}}
    <footer class="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
        <p>Sistema Biométrico Escolar · Pantalla de Supervisión Institucional</p>
        <p>Estado de Red: <span class="text-emerald-400 font-bold">100% Óptima</span></p>
    </footer>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('kiosk-clock').textContent = `${hours}:${minutes}:${seconds}`;

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('kiosk-date').textContent = now.toLocaleDateString('es-ES', options);
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@endsection

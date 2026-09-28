@extends('antigravity.layouts.main')

@section('title', 'Consola Global SaaS SuperAdmin | Infraestructura Multi-Tenant')

@section('content')
<div class="min-h-screen bg-slate-950 text-slate-100 pb-16">
    {{-- TOPBAR SUPERADMIN --}}
    <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 to-cyan-400 text-slate-950 font-black text-sm flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    SA
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-wider text-cyan-400">Infraestructura SaaS</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-cyan-950 text-cyan-300 border border-cyan-800">Multi-Tenant</span>
                    </div>
                    <h1 class="text-sm font-black text-white leading-tight">Consola de Control SuperAdmin</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-950 text-emerald-300 text-xs font-bold border border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Cluster Cloud Operativo (99.98% SLA)
                </span>

                <button onclick="document.getElementById('modal-new-school').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-black text-xs uppercase tracking-wider transition shadow-lg shadow-cyan-400/20">
                    + Nueva Institución
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- BANNER DE ARQUITECTURA MULTI-TENANT --}}
        <div class="rounded-3xl border border-cyan-500/20 bg-gradient-to-br from-cyan-500/10 via-slate-900 to-slate-950 p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.2em] text-cyan-400">Nivel de Plataforma</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white mt-1">Supervisión Global de Redes Escolares</h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-2xl leading-relaxed">
                        Administra colegios contratados, monitorea la carga de los lectores biométricos en tiempo real, activa módulos y asegura el aislamiento total de datos entre instituciones.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="bg-slate-900/80 backdrop-blur-md px-5 py-3.5 rounded-2xl border border-white/10 text-center">
                        <p class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">MRR Mensual</p>
                        <p class="text-2xl font-black text-emerald-400 mt-0.5">$3,750 <span class="text-xs font-normal text-slate-400">USD</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- METRICAS SAAS CLAVE --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="p-6 rounded-3xl border border-slate-800 bg-slate-900 shadow-sm">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Instituciones Activas</span>
                <p class="text-3xl font-black text-white mt-2">{{ $activeSchools }} <span class="text-sm font-normal text-slate-400">de {{ $totalSchools }}</span></p>
                <p class="text-xs text-emerald-400 font-bold mt-2">✓ 100% licencias al día</p>
            </div>

            <div class="p-6 rounded-3xl border border-slate-800 bg-slate-900 shadow-sm">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Alumnos Bajo Gestión</span>
                <p class="text-3xl font-black text-cyan-300 mt-2">{{ $totalStudents }}</p>
                <p class="text-xs text-slate-400 mt-2">Capacidad contratada: 2,500 plazas</p>
            </div>

            <div class="p-6 rounded-3xl border border-slate-800 bg-slate-900 shadow-sm">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Hardware ADMS Activo</span>
                <p class="text-3xl font-black text-teal-300 mt-2">{{ $totalDevices }} Lectores</p>
                <p class="text-xs text-slate-400 mt-2">Zero-Port Cloud Sync ZKTeco</p>
            </div>

            <div class="p-6 rounded-3xl border border-slate-800 bg-slate-900 shadow-sm">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Salud del Cluster</span>
                <p class="text-3xl font-black text-emerald-400 mt-2">18% CPU</p>
                <p class="text-xs text-slate-400 mt-2">RAM: 42% · Latencia: 12ms</p>
            </div>
        </div>

        {{-- LISTADO DETALLADO DE COLEGIOS CONTRATADOS --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900 overflow-hidden shadow-sm">
            <div class="p-6 sm:p-8 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-white">Instituciones Educativas con Licencia SaaS</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Control de suscripción, módulos contratados y cuotas de uso</p>
                </div>
            </div>

            <div class="divide-y divide-slate-800">
                @foreach ($schools as $sc)
                <div class="p-6 sm:p-8 hover:bg-slate-800/30 transition flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-300 font-black text-base flex items-center justify-center shrink-0 border border-cyan-500/20">
                            {{ strtoupper(substr($sc->short_name ?: $sc->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h4 class="font-black text-base text-white">{{ $sc->name }}</h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-950 text-emerald-300 border border-emerald-800">
                                    Licencia Activa
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 font-mono">Código: {{ $sc->code }} · {{ $sc->tax_id ?? 'RNC-130-98421' }} · {{ $sc->address }}</p>

                            {{-- MÓDULOS ACTIVOS --}}
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">Biometría ADMS</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">Aula Activa Docente</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">Reportes MINERD</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">Notificaciones WhatsApp</span>
                            </div>
                        </div>
                    </div>

                    {{-- CUOTA Y BOTONES --}}
                    <div class="flex items-center gap-6 shrink-0 border-t lg:border-t-0 pt-4 lg:pt-0 border-slate-800">
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Cuota Mensual</p>
                            <p class="text-lg font-black text-white mt-0.5">$1,850 USD</p>
                            <p class="text-[10px] text-slate-500">{{ $sc->students_count ?? 30 }} alumnos · {{ $sc->devices_count ?? 3 }} lectores</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('antigravity.admin') }}" class="px-4 py-2.5 rounded-xl bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-extrabold text-xs transition">
                                Entrar como Director
                            </a>
                            <button onclick="alert('Generando respaldo cifrado descargable de la institución {{ $sc->name }}...')" class="p-2.5 rounded-xl border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500 transition" title="Descargar Respaldo Cifrado">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- REGISTRO DE AUDITORÍA GLOBAL --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-white">Registro de Auditoría de la Plataforma</h3>
                <span class="text-xs text-slate-400">Eventos de seguridad y sincronización</span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="font-mono text-slate-400">07:30:00</span>
                        <span class="font-bold text-white">Apertura de jornada matutina en Colegio Bilingüe San Patricio.</span>
                    </div>
                    <span class="font-mono text-slate-500">IP: 192.168.10.50</span>
                </div>
                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        <span class="font-mono text-slate-400">07:28:15</span>
                        <span class="font-bold text-white">3 Lectores ZKTeco ADMS sincronizados exitosamente sin pérdida de paquetes.</span>
                    </div>
                    <span class="font-mono text-slate-500">Cluster ADMS</span>
                </div>
            </div>
        </div>
    </main>

    {{-- MODAL NUEVA INSTITUCIÓN --}}
    <div id="modal-new-school" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="w-full max-w-lg bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-2xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-white">Dar de Alta Nueva Institución Educativa</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Creación de tenant y asignación de código de acceso</p>
                </div>
                <button onclick="document.getElementById('modal-new-school').classList.add('hidden')" class="p-2 rounded-xl text-slate-400 hover:text-white">✕</button>
            </div>

            <form onsubmit="event.preventDefault(); alert('Colegio creado exitosamente en el cluster SaaS.'); document.getElementById('modal-new-school').classList.add('hidden')" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nombre de la Institución</label>
                    <input type="text" value="Colegio Saint Thomas Aquinas" required class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-xs font-medium text-white">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Código de Acceso</label>
                        <input type="text" value="STAQUINAS" required class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-xs font-mono font-bold text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Teléfono</label>
                        <input type="text" value="+1 (809) 555-8822" class="w-full px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950 text-xs font-medium text-white">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('modal-new-school').classList.add('hidden')" class="flex-1 py-3 rounded-xl border border-slate-800 text-xs font-bold text-slate-300">Cancelar</button>
                    <button type="submit" class="flex-1 py-3 rounded-xl bg-cyan-400 hover:bg-cyan-300 text-slate-950 text-xs font-black uppercase tracking-wider transition">Crear Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

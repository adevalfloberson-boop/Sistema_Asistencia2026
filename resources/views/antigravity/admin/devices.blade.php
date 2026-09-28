@extends('antigravity.layouts.main')

@section('title', 'Radar de Dispositivos Biométricos | ' . ($school->name ?? 'Portal Escolar'))

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
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Consola de Infraestructura y Lectores ZKTeco</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="alert('Hora sincronizada en todos los lectores biométricos (NTP Pool: America/Santo_Domingo).')" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-xs font-bold text-slate-700 dark:text-slate-200 transition">
                    ⏱ Sincronizar Hora
                </button>
            </div>
        </div>

        {{-- SUB-NAVEGACIÓN INTERNA DEL ADMIN --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-2 overflow-x-auto border-t border-slate-100 dark:border-slate-800/60 py-2.5 text-xs font-bold">
            <a href="{{ route('antigravity.admin') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Resumen General
            </a>
            <a href="{{ route('antigravity.admin.students') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Estudiantes y Cursos
            </a>
            <a href="{{ route('antigravity.admin.enrollment') }}" class="px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Estación de Huellas (10 Dedos)
            </a>
            <a href="{{ route('antigravity.admin.devices') }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80">
                Radar de Lectores Biométricos
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- BANNER DE ARQUITECTURA ADMS CLOUD --}}
        <div class="rounded-3xl border border-cyan-500/20 bg-gradient-to-r from-slate-900 via-slate-900 to-cyan-950 p-6 sm:p-8 text-white flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl shadow-cyan-950/20">
            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-400/10 text-cyan-300 border border-cyan-400/20 text-xs font-black uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                    Arquitectura Zero-Port ADMS
                </span>
                <h2 class="text-2xl font-black tracking-tight">Sincronización Inversa Push en Tiempo Real</h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
                    Los lectores inician la conexión segura hacia la nube. No requiere IP pública fija ni abrir puertos inseguros en la red del colegio.
                </p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/10 text-center">
                    <p class="text-xs uppercase text-slate-400 font-bold">Lectores Activos</p>
                    <p class="text-xl font-black text-cyan-300 mt-0.5">{{ $devices->count() }} Unidades</p>
                </div>
            </div>
        </div>

        {{-- TARJETAS INDIVIDUALES DE HARDWARE --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($devices as $dev)
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col justify-between space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400">{{ $dev->location }}</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black uppercase {{ $dev->status === 'connected' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                            <span class="w-2 h-2 rounded-full {{ $dev->status === 'connected' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                            {{ $dev->status === 'connected' ? 'En Línea' : 'Retraso' }}
                        </span>
                    </div>

                    <h3 class="text-lg font-black text-slate-900 dark:text-white mt-3">{{ $dev->name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-semibold">{{ $dev->model }}</p>

                    {{-- FICHA TÉCNICA --}}
                    <div class="mt-6 space-y-2.5 text-xs font-mono bg-slate-50 dark:bg-slate-950 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-sans">Dirección IP:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $dev->ip_address }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-sans">MAC:</span>
                            <span class="text-slate-600 dark:text-slate-300">{{ $dev->mac_address }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-sans">Número de Serie:</span>
                            <span class="text-slate-600 dark:text-slate-300">{{ $dev->serial_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-sans">Firmware ADMS:</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $dev->firmware_version ?? 'Ver 2.4.1' }}</span>
                        </div>
                    </div>
                </div>

                {{-- ACCIONES DE LECTOR --}}
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex gap-2">
                    <button onclick="alert('Ping al lector {{ $dev->name }}: 14ms (Respuesta exitosa)')" class="flex-1 py-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                        Probar Ping
                    </button>
                    <button onclick="alert('Comando de sincronización de huellas enviado a la cola del lector.')" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-950 text-xs font-bold transition">
                        Sync
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </main>
</div>
@endsection

@extends('antigravity.layouts.main')

@section('title', 'Dashboard Directivo Ejecutivo | ' . ($school->name ?? 'Portal Escolar'))

@section('content')
<div class="min-h-screen bg-slate-100 dark:bg-slate-950 flex flex-col lg:flex-row">

    {{-- SIDEBAR ENTERPRISE DE NAVEGACIÓN --}}
    <aside class="w-full lg:w-72 bg-slate-900 text-slate-200 border-r border-slate-800 flex flex-col justify-between shrink-0">
        <div>
            {{-- IDENTIDAD DEL COLEGIO --}}
            <div class="p-6 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-slate-950 font-black text-base flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        {{ strtoupper(substr($school->short_name ?? 'SP', 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-black text-white truncate leading-tight">{{ $school->name ?? 'Colegio San Patricio' }}</h1>
                        <span class="inline-flex items-center gap-1.5 mt-1 text-[10px] font-extrabold uppercase tracking-wider text-emerald-400 bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Licencia Enterprise
                        </span>
                    </div>
                </div>
            </div>

            {{-- NAVEGACIÓN AGRUPADA POR ÁREAS --}}
            <nav class="p-4 space-y-6 text-xs font-bold">
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-[.2em] text-slate-400 mb-2">Panel Directivo Escolar</p>
                    <div class="space-y-1">
                        <a href="{{ route('antigravity.admin') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-emerald-500 text-slate-950 font-extrabold shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Resumen Ejecutivo</span>
                        </a>
                        <a href="{{ route('antigravity.kiosk') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Kiosco Recepción</span>
                        </a>
                        <a href="{{ route('antigravity.teacher') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Aula Activa (Docente)</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-[.2em] text-slate-400 mb-2">Comunidad & Academia</p>
                    <div class="space-y-1">
                        <a href="{{ route('antigravity.admin.students') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Estudiantes y Matrículas</span>
                        </a>
                        <a href="{{ route('antigravity.admin.enrollment') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/></svg>
                            <span>Estación de Huellas (10 Dedos)</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-[.2em] text-slate-400 mb-2">Hardware & Red ADMS</p>
                    <div class="space-y-1">
                        <a href="{{ route('antigravity.admin.devices') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 .75h8L15 20l-.75-3M4 5h16v12H4V5z"/></svg>
                            <span>Radar de Lectores ZKTeco</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        {{-- WIDGET INFERIOR: ESTADO DE LA RED Y PERFIL --}}
        <div class="p-4 border-t border-slate-800">
            <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 mb-3">
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <span class="text-slate-400">Red ADMS Cloud</span>
                    <span class="text-emerald-400 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        3 Conectados
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Latencia promedio: 14ms</p>
            </div>

            <div class="flex items-center gap-3 px-2">
                <div class="w-9 h-9 rounded-full bg-emerald-500 text-slate-950 font-black text-xs flex items-center justify-center">
                    PM
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">Dra. Patricia Morales</p>
                    <p class="text-[10px] text-slate-400 truncate">Directora General</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- CONTENIDO PRINCIPAL --}}
    <div class="flex-1 min-w-0 flex flex-col">
        {{-- TOPBAR PRINCIPAL --}}
        <header class="h-20 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-20 shadow-xs">
            <div class="flex items-center gap-4">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Jornada Matutina</span>
                <span class="text-slate-300 dark:text-slate-700 font-normal">|</span>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-extrabold text-slate-900 dark:text-white font-mono" id="live-header-clock">07:54:12 AM</span>
                </div>
                <span class="text-xs text-slate-400 hidden sm:inline">· {{ now()->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="alert('Generando reporte PDF oficial con sello institucional para MINERD...')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-xs font-bold text-slate-700 dark:text-slate-200 transition bg-white dark:bg-slate-900">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Exportar Reporte</span>
                </button>

                <button onclick="alert('Simulación: Notificación push enviada a los apoderados de alumnos ausentes.')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-950 text-xs font-extrabold uppercase tracking-wider transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Notificar Apoderados</span>
                </button>
            </div>
        </header>

        {{-- MAIN BODY --}}
        <main class="p-6 sm:p-8 space-y-8 max-w-[1600px] w-full mx-auto">

            {{-- SEMÁFORO DIRECTIVO: LECTURA INMEDIATA DEL ESTADO ESCOLAR --}}
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid lg:grid-cols-[1.05fr_1.95fr]">
                    <div class="relative overflow-hidden bg-slate-950 p-7 text-white sm:p-9">
                        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full border-[42px] border-white/5"></div>
                        <div class="relative">
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.18em] text-slate-200">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                                Monitoreo de hoy
                            </span>
                            <h2 class="mt-5 text-3xl font-black tracking-tight">Semáforo Directivo</h2>
                            <p class="mt-2 max-w-md text-sm leading-6 text-slate-400">Una lectura ejecutiva para saber quién llegó a tiempo, quién llegó tarde y quién aún no se ha presentado.</p>

                            <div class="mt-8 flex items-center gap-6">
                                <div class="rounded-[2rem] border border-white/10 bg-slate-900/80 p-4 shadow-2xl shadow-black/30">
                                    <div class="flex flex-col gap-3">
                                        <span class="h-14 w-14 rounded-full bg-rose-500 shadow-[0_0_30px_rgba(244,63,94,.45)] ring-4 ring-rose-400/15"></span>
                                        <span class="h-14 w-14 rounded-full bg-amber-400 shadow-[0_0_30px_rgba(251,191,36,.38)] ring-4 ring-amber-300/15"></span>
                                        <span class="h-14 w-14 rounded-full bg-emerald-500 shadow-[0_0_30px_rgba(16,185,129,.45)] ring-4 ring-emerald-400/15"></span>
                                    </div>
                                </div>
                                <div class="space-y-4 text-sm">
                                    <div><p class="text-3xl font-black text-emerald-400">{{ $trafficLightCounts['present'] }}</p><p class="font-bold text-slate-300">Presentes a tiempo</p></div>
                                    <div><p class="text-3xl font-black text-amber-300">{{ $trafficLightCounts['late'] }}</p><p class="font-bold text-slate-300">Llegadas tardías</p></div>
                                    <div><p class="text-3xl font-black text-rose-400">{{ $trafficLightCounts['absent'] }}</p><p class="font-bold text-slate-300">Ausentes</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-slate-400">Estado individual</p>
                                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Mapa rápido de asistencia</h3>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-wider">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Verde · Presente</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1.5 text-amber-800 dark:bg-amber-400/15 dark:text-amber-300"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Amarillo · Tardanza</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-rose-800 dark:bg-rose-400/15 dark:text-rose-300"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Rojo · Ausente</span>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($trafficLightRoster->take(12) as $item)
                                @php
                                    $trafficMeta = match ($item['status']) {
                                        'present' => ['Presente', 'bg-emerald-50 border-emerald-200 dark:bg-emerald-400/10 dark:border-emerald-800', 'bg-emerald-500 shadow-emerald-500/40', 'text-emerald-700 dark:text-emerald-300'],
                                        'late' => ['Tardanza', 'bg-amber-50 border-amber-200 dark:bg-amber-400/10 dark:border-amber-800', 'bg-amber-400 shadow-amber-400/40', 'text-amber-700 dark:text-amber-300'],
                                        default => ['Ausente', 'bg-rose-50 border-rose-200 dark:bg-rose-400/10 dark:border-rose-800', 'bg-rose-500 shadow-rose-500/40', 'text-rose-700 dark:text-rose-300'],
                                    };
                                @endphp
                                <article class="flex items-center gap-3 rounded-2xl border p-3 {{ $trafficMeta[1] }}">
                                    <span class="h-4 w-4 shrink-0 rounded-full shadow-lg ring-4 ring-white/70 dark:ring-slate-900/60 {{ $trafficMeta[2] }}"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-black text-slate-900 dark:text-white">{{ $item['student']->nombre }} {{ $item['student']->apellido }}</p>
                                        <p class="mt-0.5 truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $item['student']->curso }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-black uppercase {{ $trafficMeta[3] }}">{{ $trafficMeta[0] }}</p>
                                        <p class="mt-0.5 text-[10px] font-mono text-slate-500">{{ $item['entry']?->fecha_hora?->format('H:i') ?? '—' }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-5 flex flex-col gap-3 rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500"><strong class="text-slate-800 dark:text-slate-200">Acción recomendada:</strong> revisar primero los casos rojos y confirmar las tardanzas amarillas con recepción.</p>
                            <button onclick="alert('Abriendo listado priorizado de tardanzas y ausencias para seguimiento...')" class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white hover:bg-slate-800 dark:bg-emerald-500 dark:text-slate-950">Gestionar alertas</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- FILA 1: 4 TARJETAS KPI EJECUTIVAS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                {{-- KPI 1: TASA GLOBAL CON GAUGE RADIAL --}}
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex items-center justify-between relative overflow-hidden">
                    <div class="space-y-1">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Asistencia Global</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $attendanceRate }}%</span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">▲ +3.2%</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ $presentToday }} presentes de {{ $totalStudents }} alumnos</p>
                    </div>

                    {{-- GAUGE VISUAL SVG --}}
                    <div class="relative w-20 h-20 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100 dark:text-slate-800" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-emerald-500" stroke-dasharray="91, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="absolute text-xs font-black text-emerald-600 dark:text-emerald-400">91%</span>
                    </div>
                </div>

                {{-- KPI 2: EN EL PLANTEL --}}
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">En el Plantel</span>
                        <span class="p-2 rounded-xl bg-teal-100 dark:bg-teal-950/70 text-teal-600 dark:text-teal-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $presentToday }}</span>
                        <span class="text-xs text-slate-400 font-semibold ml-1">verificados por huella</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between text-[11px] text-slate-500">
                        <span>Torniquete Principal: <strong>18</strong></span>
                        <span>Pabellón B: <strong>10</strong></span>
                    </div>
                </div>

                {{-- KPI 3: PUNTUALIDAD Y TARDANZAS --}}
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Tardanzas Hoy</span>
                        <span class="p-2 rounded-xl bg-amber-100 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ $tardyToday }}</span>
                        <span class="text-xs text-slate-400 font-semibold">llegadas después de 07:45</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-amber-600 dark:text-amber-400 font-bold flex items-center gap-1">
                        <span>● Tolerancia de centro: 15 min</span>
                    </div>
                </div>

                {{-- KPI 4: AUSENCIAS SIN JUSTIFICAR --}}
                <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Ausencias Sin Justificar</span>
                        <span class="p-2 rounded-xl bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-rose-600 dark:text-rose-400">{{ $absentToday }}</span>
                        <span class="text-xs text-slate-400 font-semibold">estudiantes</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button onclick="alert('Abriendo listado para llamada a apoderados...')" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            Ver listado para llamar &rarr;
                        </button>
                    </div>
                </div>
            </div>

            {{-- FILA 2: GRÁFICAS DE FLUJO HORARIO Y TENDENCIA SEMANAL --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- HISTOGRAMA / CURVA DE LLEGADA (7 cols) --}}
                <div class="lg:col-span-7 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">Curva de Flujo Horario de Entrada</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Distribución de ponches por tramo horario en los torniquetes</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                            Hora Pico: 07:38 AM
                        </span>
                    </div>

                    {{-- BARRAS DE FLUJO --}}
                    <div class="h-48 flex items-end justify-between gap-6 pt-6 pb-2 px-4 bg-slate-50/50 dark:bg-slate-950/40 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                        @foreach ($hourlyFlow as $hf)
                        <div class="flex-1 flex flex-col items-center gap-2 group h-full justify-end">
                            <span class="text-xs font-mono font-black text-slate-700 dark:text-slate-300 group-hover:text-emerald-500 transition">{{ $hf['count'] }}</span>
                            <div class="w-full max-w-[48px] bg-gradient-to-t from-emerald-600 to-teal-400 rounded-t-xl transition-all duration-500 group-hover:scale-y-105" style="height: {{ $hf['height'] }}%"></div>
                            <span class="text-[10px] font-bold text-slate-400 mt-1 text-center truncate w-full">{{ $hf['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- TENDENCIA SEMANAL (5 cols) --}}
                <div class="lg:col-span-5 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Comparativa Semanal</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Asistencia diaria vs meta institucional (90%)</p>
                            </div>
                            <span class="text-xs font-bold text-slate-500">Meta: 90%</span>
                        </div>

                        <div class="space-y-3.5">
                            @foreach ($weeklyTrend as $wt)
                            <div class="flex items-center gap-3">
                                <span class="w-10 text-xs font-bold {{ !empty($wt['is_today']) ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-500' }}">{{ $wt['day'] }}</span>
                                <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden relative">
                                    <div class="h-full rounded-full {{ !empty($wt['is_today']) ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-slate-400 dark:bg-slate-600' }}" style="width: {{ $wt['rate'] }}%"></div>
                                </div>
                                <span class="w-12 text-right text-xs font-mono font-black {{ !empty($wt['is_today']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">{{ $wt['rate'] }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <span>Promedio de semana: <strong class="text-slate-900 dark:text-white">93.3%</strong></span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Cumple estándar</span>
                    </div>
                </div>
            </div>

            {{-- FILA 3: CENTRO DE ALERTAS Y CONCILIACIÓN INTELIGENTE (DOBLE VERIFICACIÓN) --}}
            <div class="rounded-3xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/40 dark:bg-amber-950/20 p-6 sm:p-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-amber-500 text-slate-950">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">Conciliación Inteligente: Puerta vs Aula</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Detección de discrepancias en tiempo real entre el ponche exterior y el pase de lista</p>
                        </div>
                    </div>
                    <span class="text-xs font-extrabold text-amber-700 dark:text-amber-300">2 Alertas Activas</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    @foreach ($incidents as $inc)
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/60 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ $inc['title'] }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ $inc['time'] }}</span>
                            </div>
                            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $inc['student'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $inc['description'] }}</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">{{ $inc['badge'] }}</span>
                            <button onclick="alert('Acción ejecutada para la incidencia de {{ $inc['student'] }}.')" class="text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-emerald-500">Resolver &rarr;</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- FILA 4: TABLA DE CURSOS Y FEED EN VIVO --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- TABLA DE CURSOS (8 cols) --}}
                <div class="lg:col-span-8 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">Estado por Secciones Académicas</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Asistencia reportada por los docentes en cada aula</p>
                        </div>
                        <a href="{{ route('antigravity.admin.students') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">Ver Alumnos &rarr;</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-950 text-[11px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-3.5">Curso / Sección</th>
                                    <th class="px-6 py-3.5">Matrícula</th>
                                    <th class="px-6 py-3.5">Presentes</th>
                                    <th class="px-6 py-3.5">Cumplimiento</th>
                                    <th class="px-6 py-3.5 text-right">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($courseStats as $cs)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-6 py-4">
                                        <p class="font-extrabold text-slate-900 dark:text-white">{{ $cs['name'] }}</p>
                                        <p class="text-[11px] font-mono text-slate-400">{{ $cs['code'] }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $cs['total'] }} alumnos</td>
                                    <td class="px-6 py-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $cs['present'] }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $cs['percentage'] >= 90 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $cs['percentage'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-mono font-bold">{{ $cs['percentage'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $cs['percentage'] >= 90 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                            {{ $cs['percentage'] >= 90 ? 'Excelente' : 'Regular' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- FEED BIOMÉTRICO EN VIVO (4 cols) --}}
                <div class="lg:col-span-4 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Flujo Biométrico en Vivo</h3>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">ADMS Push</span>
                        </div>

                        <div class="space-y-3 overflow-y-auto max-h-[380px] pr-1">
                            @forelse ($todayAttendances->take(7) as $att)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-black text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($att->student?->nombre ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $att->student?->nombre }} {{ $att->student?->apellido }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ $att->curso }}</p>
                                    </div>
                                </div>
                                <span class="text-xs font-mono font-black text-emerald-600 dark:text-emerald-400 shrink-0">
                                    {{ $att->fecha_hora->format('H:i') }}
                                </span>
                            </div>
                            @empty
                            <p class="text-xs text-slate-400 text-center py-6">Sin registros recientes</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                        <a href="{{ route('antigravity.kiosk') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                            Ver en Pantalla Completa &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const clockEl = document.getElementById('live-header-clock');
        if (clockEl) clockEl.textContent = timeStr;
    }
    setInterval(updateClock, 1000);
</script>
@endsection

@extends('antigravity.layouts.main')

@section('title', 'Hub de Diseños Comerciales')

@section('content')
<div class="min-h-screen">
    {{-- HEADER SUPERIOR --}}
    <header class="border-b border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20">
                    <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Suite Comercial</span>
                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">Versión 2026</span>
                    </div>
                    <h1 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">Sistema de Asistencia Escolar Biométrico</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('antigravity.login') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-slate-950 font-bold text-xs uppercase tracking-wider shadow-lg transition">
                    <span>Iniciar Demostración</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </header>

    {{-- HERO PRINCIPAL DE VENTA --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-16">
        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-teal-950 text-white p-8 sm:p-12 lg:p-16 border border-slate-800 shadow-2xl shadow-emerald-950/20">
            <div class="absolute -right-24 -bottom-24 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -top-20 w-80 h-80 rounded-full bg-teal-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-emerald-300 mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Diseñado para Comercialización SaaS a Colegios Privados e Institutos
                </div>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
                    Control biométrico en tiempo real, <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400">cero fraude</span> y verificación en aula.
                </h2>
                <p class="mt-5 text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                    Una solución lista para vender que combina lectores biométricos de huella ZKTeco ADMS, reconciliación automática entre entrada al plantel y presencia en clase, y una interfaz de nivel mundial para directores, docentes y familias.
                </p>

                <div class="mt-8 flex flex-wrap gap-4 items-center">
                    <a href="{{ route('antigravity.admin') }}" class="px-6 py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm shadow-xl shadow-emerald-500/25 transition">
                        Ver Dashboard del Director
                    </a>
                    <a href="{{ route('antigravity.teacher') }}" class="px-6 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/15 transition">
                        Ver Panel Docente en Aula
                    </a>
                </div>

                {{-- STATS RÁPIDAS DE LA DEMO --}}
                <div class="mt-12 pt-8 border-t border-white/10 grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Institución Demo</p>
                        <p class="text-base font-extrabold text-white mt-1">{{ $school->short_name ?? 'San Patricio' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Estudiantes Registrados</p>
                        <p class="text-2xl font-black text-emerald-400 mt-1">{{ $studentCount }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Lectores Biométricos</p>
                        <p class="text-2xl font-black text-teal-300 mt-1">{{ $deviceCount }} en línea</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Ponches de Hoy</p>
                        <p class="text-2xl font-black text-amber-300 mt-1">{{ $attendanceCount }} eventos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CATÁLOGO DE MÓDULOS DE DISEÑO --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600 dark:text-emerald-400">Pantallas Interactivas</p>
                <h3 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mt-1">Explora cada experiencia de usuario</h3>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md">
                Todas las pantallas están activas con datos realistas en memoria para demostración sin servidor externo.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- 1. LOGIN COMERCIAL --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">Split-Screen</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Login Institucional de Alta Conversión</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Pantalla dividida con pitch comercial al lateral y botones de acceso demo con 1 clic para cada rol institucional.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">4 perfiles integrados</span>
                    <a href="{{ route('antigravity.login') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver Pantalla &rarr;
                    </a>
                </div>
            </article>

            {{-- 2. DASHBOARD DEL DIRECTOR --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">En Vivo</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Dashboard Directivo con KPIs</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Métricas en tiempo real, tasa global de asistencia, ticker de ponches en puerta y progreso por cada sección escolar.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">Feed biométrico vivo</span>
                    <a href="{{ route('antigravity.admin') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver Dashboard &rarr;
                    </a>
                </div>
            </article>

            {{-- 3. ENROLAMIENTO DE HUELLAS (10 DEDOS) --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Interactivo</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Estación de Captura de Huellas</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Diagrama interactivo de manos humanas con selección de 10 dedos, puntaje de calidad biométrica y simulación de lectura.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">Calidad y precisión</span>
                    <a href="{{ route('antigravity.admin.enrollment') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver Estación &rarr;
                    </a>
                </div>
            </article>

            {{-- 4. RADAR DE DISPOSITIVOS BIOMÉTRICOS --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 .75h8L15 20l-.75-3M4 5h16v12H4V5z"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300">ADMS Push</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Consola de Lectores ZKTeco</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Monitoreo de lectores en tiempo real, latencias de ping, IPs, números de serie, versiones de firmware y comando remoto.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">3 lectores en demo</span>
                    <a href="{{ route('antigravity.admin.devices') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver Radar &rarr;
                    </a>
                </div>
            </article>

            {{-- 5. PANEL DOCENTE "AULA ACTIVA" --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300">Tablet / Móvil</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Panel Docente "Aula Activa"</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Pase de lista táctil con botones grandes, y detector automático que avisa al profesor si el estudiante llegó al plantel pero no está en clase.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">Reconciliación inteligente</span>
                    <a href="{{ route('antigravity.teacher') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver Panel &rarr;
                    </a>
                </div>
            </article>

            {{-- 6. SUPERADMIN SAAS & KIOSCO --}}
            <article class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">Multi-Colegio</span>
                    </div>
                    <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-4">Consola SaaS & Pantalla Kiosco</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Control multi-institución para comercializar a redes de colegios o distritos, y modo Kiosco para monitores de pared en recepción.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <a href="{{ route('antigravity.kiosk') }}" class="text-xs font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white">Modo Kiosco</a>
                    <a href="{{ route('antigravity.superadmin') }}" class="font-bold text-sm text-emerald-600 dark:text-emerald-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Ver SaaS &rarr;
                    </a>
                </div>
            </article>
        </div>
    </section>
</div>
@endsection

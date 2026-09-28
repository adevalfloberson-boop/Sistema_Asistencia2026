@extends('antigravity.layouts.main')

@section('title', 'Iniciar Sesión | Portal Institucional')

@section('content')
<div class="min-h-screen flex flex-col lg:flex-row">
    {{-- PANEL IZQUIERDO: PRESENTACIÓN DE VALOR COMERCIAL --}}
    <div class="lg:w-1/2 relative bg-gradient-to-br from-slate-950 via-slate-900 to-teal-950 p-8 sm:p-12 lg:p-16 flex flex-col justify-between text-white overflow-hidden border-b lg:border-b-0 lg:border-r border-slate-800">
        <div class="absolute -right-20 -top-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10">
            <a href="{{ route('antigravity.hub') }}" class="inline-flex items-center gap-2.5 text-white group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/></svg>
                </div>
                <div>
                    <strong class="block text-base tracking-tight font-extrabold">Portal Escolar Biométrico</strong>
                    <span class="block text-[10px] font-bold uppercase tracking-[.22em] text-emerald-400">Antigravity Edition</span>
                </div>
            </a>

            <div class="mt-16 max-w-lg">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-xs font-bold text-emerald-300 mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Seguridad Escolar & Control en Tiempo Real
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    La tranquilidad de saber que cada estudiante está en su lugar.
                </h1>

                <p class="mt-6 text-slate-300 text-sm sm:text-base leading-relaxed">
                    Sincronización instantánea con lectores biométricos ZKTeco ADMS, conciliación automática con la lista del docente en aula y notificaciones en vivo para directores.
                </p>

                {{-- BENEFICIOS CLAVE --}}
                <div class="mt-8 space-y-4 text-sm font-semibold text-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">✓</div>
                        <span>Lectores de huella y reconocimiento facial sin abrir puertos locales.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">✓</div>
                        <span>Alerta inmediata si un alumno poncha en la entrada pero falta a clase.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">✓</div>
                        <span>Arquitectura multi-institución para comercialización en distritos o colegios.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TESTIMONIO / GARANTÍA --}}
        <div class="relative z-10 mt-12 pt-8 border-t border-white/10 flex items-center justify-between text-xs text-slate-400">
            <div>
                <p class="text-white font-bold text-sm">Colegio Bilingüe San Patricio</p>
                <p class="mt-0.5">Institución modelo en demostración</p>
            </div>
            <div class="flex items-center gap-1.5 text-emerald-400 font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                3 Lectores Operativos
            </div>
        </div>
    </div>

    {{-- PANEL DERECHO: FORMULARIO Y ACCESOS RÁPIDOS DEMO --}}
    <div class="lg:w-1/2 flex items-center justify-center p-6 sm:p-12 lg:p-16 bg-white dark:bg-slate-900">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Iniciar Sesión</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Accede con las credenciales de tu centro educativo o prueba directamente con los perfiles de demostración.
                </p>
            </div>

            {{-- BOTONES DE ACCESO RÁPIDO PARA PRESENTACIÓN COMERCIAL --}}
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Acceso Rápido Demo (1 Clic)</span>
                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Listo</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('antigravity.quick-login', 'director') }}" class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500 text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition shadow-sm font-bold text-xs">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center font-black text-xs">D</span>
                        <div class="text-left leading-tight">
                            <span class="block">Directora</span>
                            <span class="text-[10px] text-slate-400 font-normal">Admin Colegio</span>
                        </div>
                    </a>

                    <a href="{{ route('antigravity.quick-login', 'docente') }}" class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500 text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition shadow-sm font-bold text-xs">
                        <span class="w-6 h-6 rounded-lg bg-teal-100 dark:bg-teal-950 text-teal-600 flex items-center justify-center font-black text-xs">P</span>
                        <div class="text-left leading-tight">
                            <span class="block">Profesor</span>
                            <span class="text-[10px] text-slate-400 font-normal">Aula Activa</span>
                        </div>
                    </a>

                    <a href="{{ route('antigravity.quick-login', 'superadmin') }}" class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500 text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition shadow-sm font-bold text-xs">
                        <span class="w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 flex items-center justify-center font-black text-xs">S</span>
                        <div class="text-left leading-tight">
                            <span class="block">SuperAdmin</span>
                            <span class="text-[10px] text-slate-400 font-normal">Consola SaaS</span>
                        </div>
                    </a>

                    <a href="{{ route('antigravity.quick-login', 'recepcion') }}" class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500 text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition shadow-sm font-bold text-xs">
                        <span class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center font-black text-xs">K</span>
                        <div class="text-left leading-tight">
                            <span class="block">Kiosco</span>
                            <span class="text-[10px] text-slate-400 font-normal">Recepción</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="relative flex items-center justify-center">
                <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                <span class="px-3 bg-white dark:bg-slate-900 text-xs font-bold uppercase tracking-wider text-slate-400 absolute">O con credenciales</span>
            </div>

            {{-- FORMULARIO --}}
            <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5" for="code">Código de Institución</label>
                    <div class="relative">
                        <input id="code" name="institution_code" type="text" value="SANPATRICIO" placeholder="SANPATRICIO (no aplica a SuperAdmin)" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-3 text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5" for="username">Usuario</label>
                    <input id="username" name="username" type="text" value="director" placeholder="director / docente01 / superadmin" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-3 text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition" required>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider" for="password">Contraseña</label>
                        <span class="text-xs text-slate-400">demo: password123</span>
                    </div>
                    <input id="password" name="password" type="password" value="password123" placeholder="••••••••" class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-3 text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition" required>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-slate-950 font-extrabold text-sm uppercase tracking-wider shadow-lg shadow-slate-900/10 dark:shadow-emerald-500/20 transition-all">
                        Ingresar al Sistema
                    </button>
                </div>
            </form>

            <p class="text-center text-xs text-slate-400">
                Sistema de Asistencia Escolar &copy; {{ date('Y') }} · Antigravity Suite
            </p>
        </div>
    </div>
</div>
@endsection

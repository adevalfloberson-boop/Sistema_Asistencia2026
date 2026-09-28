<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema de Asistencia Escolar') | Diseños Antigravity</title>

    <script>
        (() => {
            const theme = localStorage.getItem('antigravity-theme');
            const useDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', useDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased selection:bg-emerald-500 selection:text-white dark:bg-slate-950 dark:text-slate-100 pb-20">

    {{-- BARRA FLOTANTE DE CONTROL ANTIGRAVITY (Switcher de Demostración Comercial) --}}
    <nav class="fixed bottom-3 inset-x-3 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-50 flex items-center gap-1.5 p-1.5 rounded-2xl bg-slate-900/90 dark:bg-slate-900/95 backdrop-blur-xl border border-white/15 text-white shadow-2xl shadow-slate-950/40 text-xs font-semibold overflow-x-auto max-w-full">
        <a href="{{ route('antigravity.hub') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.hub') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Hub</span>
        </a>

        <div class="h-4 w-px bg-white/20 hidden sm:block"></div>

        <a href="{{ route('antigravity.login') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.login') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            <span class="hidden md:inline">Login</span>
        </a>

        <a href="{{ route('antigravity.admin') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.admin') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Director</span>
        </a>

        <a href="{{ route('antigravity.admin.students') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.admin.students') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span class="hidden sm:inline">Estudiantes</span>
        </a>

        <a href="{{ route('antigravity.admin.enrollment') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.admin.enrollment') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.187 1.745"/></svg>
            <span>Huellas</span>
        </a>

        <a href="{{ route('antigravity.admin.devices') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.admin.devices') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 .75h8L15 20l-.75-3M4 5h16v12H4V5z"/></svg>
            <span class="hidden lg:inline">Lectores</span>
        </a>

        <a href="{{ route('antigravity.teacher') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.teacher') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <span>Docente</span>
        </a>

        <a href="{{ route('antigravity.superadmin') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.superadmin') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="hidden md:inline">SaaS</span>
        </a>

        <a href="{{ route('antigravity.kiosk') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl transition {{ request()->routeIs('antigravity.kiosk') ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 .75h8L15 20l-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="hidden sm:inline">Kiosco</span>
        </a>

        <div class="h-4 w-px bg-white/20"></div>

        {{-- BOTÓN TEMA CLARO/OSCURO --}}
        <button id="theme-btn" class="p-2 rounded-xl hover:bg-white/10 text-slate-300 hover:text-white transition" title="Alternar tema">
            <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>
    </nav>

    {{-- CONTENIDO DE LA PÁGINA --}}
    @yield('content')

    <script>
        document.getElementById('theme-btn')?.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('antigravity-theme', isDark ? 'dark' : 'light');
        });
    </script>
</body>
</html>

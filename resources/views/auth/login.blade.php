<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Sistema de Asistencia</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="isolate flex min-h-screen flex-col justify-between overflow-x-hidden bg-slate-950 text-slate-300 font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <div class="pointer-events-none fixed inset-0 -z-10 bg-slate-950"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.16),_transparent_42%)]"></div>

    {{-- NAVEGACIÓN SUPERIOR --}}
    <header class="w-full px-8 py-6 flex justify-between items-center text-xs tracking-widest uppercase font-medium text-slate-400">
        <div>
            <a href="#" class="hover:text-white transition-colors">Contacto</a>
        </div>

        {{-- LOGO HEXAGONAL CENTRAL --}}
        <div class="flex items-center justify-center">
            <div class="w-10 h-10 bg-slate-800/80 border border-slate-700 rounded-lg flex items-center justify-center shadow-lg rotate-45 transform hover:rotate-0 transition-transform duration-300">
                <svg class="-rotate-45 hover:rotate-0 transition-transform duration-300 w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>

        <div>
            <a href="#" class="hover:text-white transition-colors">Plataforma</a>
        </div>
    </header>

    {{-- CONTENEDOR PRINCIPAL / FORMULARIO --}}
<main class="w-full max-w-md mx-auto px-6 py-8 flex-1 flex flex-col justify-center">

    <h1 class="text-2xl font-bold tracking-widest text-center text-white uppercase mb-10">
        Iniciar Sesión
    </h1>

    <form action="{{ route('login.attempt') }}" method="POST" class="space-y-6">

        @csrf

        @error('login')
            <p class="text-red-400 text-xs text-center">{{ $message }}</p>
        @enderror

        {{-- 1. CÓDIGO DE LA INSTITUCIÓN --}}
        <div class="relative group">
            <input
                type="text"
                id="institution_code"
                name="institution_code"
                placeholder="Código de la Institución"
                value="{{ old('institution_code') }}"
                required
                class="w-full bg-transparent border-b border-slate-600 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-400 transition-colors"
            >
        </div>

        {{-- 2. USUARIO --}}
        <div class="relative group">
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Usuario"
                value="{{ old('username') }}"
                autocomplete="username"
                required
                class="w-full bg-transparent border-b border-slate-600 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-400 transition-colors"
            >
        </div>

        {{-- 3. CONTRASEÑA --}}
        <div class="relative group">
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Contraseña"
                autocomplete="current-password"
                required
                class="w-full bg-transparent border-b border-slate-600 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-400 transition-colors"
            >
        </div>

        {{-- BOTÓN DE INICIO DE SESIÓN --}}
        <div class="pt-4">
            <button
                type="submit"
                class="w-full py-3.5 bg-emerald-500 hover:bg-emerald-400 active:bg-emerald-600 text-slate-950 font-bold uppercase tracking-wider text-xs rounded transition-all duration-200 shadow-lg shadow-emerald-500/20"
            >
                Iniciar Sesión
            </button>
        </div>

        {{-- RECORDAR Y OLVIDÓ CONTRASEÑA --}}
        <div class="flex items-center justify-between text-xs text-slate-400 pt-2">

            <label class="flex items-center gap-2 cursor-pointer hover:text-slate-200 transition-colors">
                <input
                    type="checkbox"
                    name="remember"
                    class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-emerald-500 focus:ring-0 focus:ring-offset-0"
                >
                <span>Mantener sesión</span>
            </label>

            <a href="#" class="hover:text-white transition-colors">
                ¿Olvidaste tu contraseña?
            </a>

        </div>

    </form>

</main>

    {{-- FOOTER --}}
    <footer class="py-6 text-center text-xs text-slate-600">
        <p>Sistema de Asistencia Escolar &copy; {{ date('Y') }}</p>
    </footer>

</body>
</html>

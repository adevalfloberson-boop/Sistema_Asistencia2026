<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'Sistema de Asistencia')
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900">

    <div class="min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-slate-900 text-white flex flex-col">

            {{-- LOGO --}}
            <div class="h-20 px-6 flex items-center border-b border-slate-800">

                <div>
                    <h1 class="font-bold text-lg">
                        Sistema de Asistencia
                    </h1>

                    <p class="text-xs text-slate-400 mt-1">
                        Gestión Escolar
                    </p>
                </div>

            </div>


            {{-- NAVEGACIÓN --}}
            <nav class="flex-1 px-4 py-6">

                <p class="px-3 mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Principal
                </p>


                {{-- Dashboard --}}
                <a href="/dashboard"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-slate-800 text-white">

                    <span>Dashboard</span>

                </a>


                {{-- Estudiantes --}}
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition">

                    <span>Estudiantes</span>

                </a>


                {{-- Asistencia --}}
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition">

                    <span>Asistencia</span>

                </a>


                {{-- Reportes --}}
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition">

                    <span>Reportes</span>

                </a>


                <p class="px-3 mt-8 mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Administración
                </p>


                {{-- Usuarios --}}
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition">

                    <span>Usuarios</span>

                </a>


                {{-- Configuración --}}
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition">

                    <span>Configuración</span>

                </a>

            </nav>


            {{-- USUARIO --}}
            <div class="p-4 border-t border-slate-800">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center">
                        A
                    </div>

                    <div>

                        <p class="text-sm font-medium">
                            Administrador
                        </p>

                        <p class="text-xs text-slate-500">
                            Administrador
                        </p>

                    </div>

                </div>

            </div>

        </aside>


        {{-- CONTENIDO PRINCIPAL --}}
        <main class="flex-1 min-w-0">

            {{-- TOPBAR --}}
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8">

                <div>

                    <h2 class="text-xl font-semibold">
                        @yield('page-title', 'Dashboard')
                    </h2>

                    <p class="text-sm text-slate-500">
                        @yield('page-description', 'Resumen del sistema')
                    </p>

                </div>


                {{-- NOTIFICACIONES --}}
                <button class="p-2 text-slate-500 hover:text-slate-900">

                    Notificaciones

                </button>

            </header>


            {{-- CONTENIDO DE LA PÁGINA --}}
            <section class="p-8">

                @yield('content')

            </section>

        </main>

    </div>

</body>
</html>
@extends('layouts.app')

@section('title', 'Dashboard | Sistema de Asistencia')

@section('page-title', 'Dashboard')

@section('page-description', 'Resumen general del sistema de asistencia')

@section('content')

    <!-- BIENVENIDA -->
    <div class="mb-8">

        <h3 class="text-2xl font-bold">
            Buenos días, Administrador
        </h3>

        <p class="text-slate-400 mt-1">
            Aquí tienes un resumen de la actividad de hoy.
        </p>

    </div>


    <!-- ESTADÍSTICAS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

            <p class="text-sm text-slate-400">
                Estudiantes
            </p>

            <p class="text-3xl font-bold mt-2">
                600
            </p>

            <p class="text-xs text-slate-500 mt-2">
                Registrados en el sistema
            </p>

        </div>


        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

            <p class="text-sm text-slate-400">
                Presentes hoy
            </p>

            <p class="text-3xl font-bold mt-2">
                542
            </p>

            <p class="text-xs text-slate-500 mt-2">
                90.3% de asistencia
            </p>

        </div>


        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

            <p class="text-sm text-slate-400">
                Ausentes
            </p>

            <p class="text-3xl font-bold mt-2">
                58
            </p>

            <p class="text-xs text-slate-500 mt-2">
                Pendientes de justificar
            </p>

        </div>


        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">

            <p class="text-sm text-slate-400">
                Lectores activos
            </p>

            <p class="text-3xl font-bold mt-2">
                8
            </p>

            <p class="text-xs text-slate-500 mt-2">
                Funcionando correctamente
            </p>

        </div>

    </div>


    <!-- CONTENIDO INFERIOR -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- ASISTENCIA -->
        <div class="xl:col-span-2 bg-slate-900 border border-slate-800 rounded-xl">

            <div class="p-6 border-b border-slate-800">

                <div class="flex items-center justify-between">

                    <div>
                        <h3 class="font-semibold">
                            Asistencia de hoy
                        </h3>

                        <p class="text-sm text-slate-500 mt-1">
                            Registro de entradas
                        </p>
                    </div>

                    <button class="text-sm text-slate-400 hover:text-white">
                        Ver todo
                    </button>

                </div>

            </div>


            <div class="divide-y divide-slate-800">

                <div class="p-5 flex items-center justify-between">

                    <div>
                        <p class="font-medium">
                            Entrada general
                        </p>

                        <p class="text-sm text-slate-500">
                            7:00 AM - 8:00 AM
                        </p>
                    </div>

                    <p class="font-semibold">
                        542 estudiantes
                    </p>

                </div>


                <div class="p-5 flex items-center justify-between">

                    <div>
                        <p class="font-medium">
                            Entrada tardía
                        </p>

                        <p class="text-sm text-slate-500">
                            Después de las 8:00 AM
                        </p>
                    </div>

                    <p class="font-semibold">
                        23 estudiantes
                    </p>

                </div>

            </div>

        </div>


        <!-- ESTADO DEL SISTEMA -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl">

            <div class="p-6 border-b border-slate-800">

                <h3 class="font-semibold">
                    Estado del sistema
                </h3>

            </div>

            <div class="p-6 space-y-5">

                <div class="flex items-center justify-between">

                    <span class="text-sm text-slate-400">
                        Servidor
                    </span>

                    <span class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Operativo
                    </span>

                </div>


                <div class="flex items-center justify-between">

                    <span class="text-sm text-slate-400">
                        Base de datos
                    </span>

                    <span class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Operativa
                    </span>

                </div>


                <div class="flex items-center justify-between">

                    <span class="text-sm text-slate-400">
                        Lectores
                    </span>

                    <span class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        8 activos
                    </span>

                </div>

            </div>

        </div>

    </div>

@endsection
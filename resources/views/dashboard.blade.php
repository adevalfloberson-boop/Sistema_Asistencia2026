@extends('layouts.app')

@section('title', 'Dashboard | Sistema de Asistencia')

@section('page-title', 'Dashboard')

@section('page-description', 'Resumen general del sistema de asistencia')

@section('content')

    <div class="mb-8">

        <h3 class="text-2xl font-bold">
            Buenos días, Administrador
        </h3>

        <p class="text-slate-500 mt-1">
            Aquí tienes un resumen de la actividad de hoy.
        </p>

    </div>


    {{-- ESTADÍSTICAS --}}

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <div class="bg-white border border-slate-200 rounded-xl p-6">

            <p class="text-sm text-slate-500">
                Estudiantes
            </p>

            <p class="text-3xl font-bold mt-2">
                600
            </p>

            <p class="text-xs text-slate-400 mt-2">
                Registrados
            </p>

        </div>


        <div class="bg-white border border-slate-200 rounded-xl p-6">

            <p class="text-sm text-slate-500">
                Presentes hoy
            </p>

            <p class="text-3xl font-bold mt-2">
                542
            </p>

            <p class="text-xs text-slate-400 mt-2">
                90.3% de asistencia
            </p>

        </div>


        <div class="bg-white border border-slate-200 rounded-xl p-6">

            <p class="text-sm text-slate-500">
                Ausentes
            </p>

            <p class="text-3xl font-bold mt-2">
                58
            </p>

            <p class="text-xs text-slate-400 mt-2">
                Pendientes
            </p>

        </div>


        <div class="bg-white border border-slate-200 rounded-xl p-6">

            <p class="text-sm text-slate-500">
                Lectores activos
            </p>

            <p class="text-3xl font-bold mt-2">
                8
            </p>

            <p class="text-xs text-slate-400 mt-2">
                Funcionando
            </p>

        </div>

    </div>

@endsection
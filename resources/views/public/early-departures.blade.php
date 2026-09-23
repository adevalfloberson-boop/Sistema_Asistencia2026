<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Autorizaciones de salida · {{ $school->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:py-10">
        <header class="flex flex-col gap-4 border-b border-slate-800 pb-6 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-[11px] font-black uppercase tracking-[.2em] text-teal-400">{{ $school->name }}</p><h1 class="mt-1 text-2xl font-black sm:text-3xl">Autorizaciones de salida</h1><p class="mt-2 text-sm text-slate-400">Selecciona uno o varios estudiantes con permiso para salir antes de las {{ $school->attendance_exit_time?->format('H:i') ?? '14:00' }}.</p></div><div class="rounded-xl bg-slate-900 px-4 py-3 text-right"><p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Fecha activa</p><p class="mt-1 font-black">{{ today()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p></div></header>

        @if (session('success'))<div class="mt-5 rounded-xl border border-emerald-800 bg-emerald-950/60 px-4 py-3 text-sm font-bold text-emerald-200">✓ {{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mt-5 rounded-xl border border-rose-800 bg-rose-950/60 px-4 py-3 text-sm font-bold text-rose-200">Revisa los datos e intenta nuevamente.</div>@endif

        <section class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                <form class="border-b border-slate-800 p-4" method="GET"><label class="sr-only" for="search">Buscar estudiante</label><div class="flex gap-2"><input id="search" class="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm" type="search" name="search" value="{{ $search }}" placeholder="Nombre, matrícula o ID biométrico…"><button class="rounded-xl bg-slate-700 px-4 py-3 text-sm font-black" type="submit">Buscar</button></div></form>
                <form id="authorization-form" action="{{ route('public.early-departures.store', $token) }}" method="POST">@csrf
                    <div class="max-h-[34rem] overflow-y-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="sticky top-0 bg-slate-950 text-[10px] uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3"><span class="sr-only">Seleccionar</span></th><th class="px-4 py-3">Estudiante</th><th class="px-4 py-3">Curso</th><th class="px-4 py-3">Biometría</th><th class="px-4 py-3">Estado hoy</th></tr></thead><tbody class="divide-y divide-slate-800">
                        @forelse ($students as $student)
                            @php($authorization = $authorizations->get($student->id))
                            <tr class="hover:bg-slate-800/50"><td class="px-4 py-4"><input class="h-5 w-5 accent-teal-500" type="checkbox" name="student_ids[]" value="{{ $student->id }}" @disabled($authorization?->used_at)></td><td class="px-4 py-4"><p class="font-black">{{ $student->nombre }} {{ $student->apellido }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $student->matricula }}</p></td><td class="px-4 py-4 text-slate-300">{{ $student->course?->name ?? $student->curso }}</td><td class="px-4 py-4 font-mono text-xs">ID {{ $student->id_lector }}</td><td class="px-4 py-4">@if ($authorization?->used_at)<span class="font-black text-sky-400">Salida registrada</span>@elseif ($authorization)<span class="font-black text-emerald-400">Autorizado</span>@else<span class="text-slate-500">Sin autorización</span>@endif</td></tr>
                        @empty<tr><td class="px-4 py-10 text-center text-slate-500" colspan="5">No se encontraron estudiantes.</td></tr>@endforelse
                    </tbody></table></div>
                    <div class="border-t border-slate-800 p-4 lg:hidden"><button class="w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-black" type="submit">Autorizar seleccionados</button></div>
                </form>
                @if ($students->hasPages())<div class="border-t border-slate-800 p-4">{{ $students->links() }}</div>@endif
            </div>

            <aside class="space-y-4"><div class="rounded-2xl border border-slate-800 bg-slate-900 p-5"><p class="text-[11px] font-black uppercase tracking-[.18em] text-teal-400">Nueva autorización</p><label class="mt-4 block text-xs font-bold text-slate-400">Autorizado por<input class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-3 text-sm" form="authorization-form" name="authorized_by_name" required placeholder="Nombre del responsable"></label><label class="mt-4 block text-xs font-bold text-slate-400">Motivo opcional<input class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-3 text-sm" form="authorization-form" name="reason" maxlength="255" placeholder="Cita, emergencia…"></label><button class="mt-5 hidden w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-black lg:block" form="authorization-form" type="submit">Autorizar seleccionados</button><p class="mt-3 text-xs leading-relaxed text-slate-500">La autorización solo es válida hoy y se consume cuando el lector registra la salida.</p></div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5"><div class="flex items-center justify-between"><p class="text-sm font-black">Autorizados hoy</p><span class="rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-black text-emerald-400">{{ $authorizations->whereNull('used_at')->count() }}</span></div><div class="mt-4 space-y-3">@forelse ($authorizations as $authorization)<div class="rounded-xl bg-slate-950 p-3"><div class="flex items-start justify-between gap-2"><div><p class="text-sm font-bold">{{ $authorization->student?->nombre }} {{ $authorization->student?->apellido }}</p><p class="mt-1 text-xs text-slate-500">{{ $authorization->authorized_by_name }}{{ $authorization->reason ? ' · '.$authorization->reason : '' }}</p></div>@if (! $authorization->used_at)<form action="{{ route('public.early-departures.destroy', [$token, $authorization]) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-black text-rose-400" type="submit">Retirar</button></form>@endif</div></div>@empty<p class="text-xs text-slate-500">Ninguna autorización activa.</p>@endforelse</div></div>
            </aside>
        </section>
    </main>
</body>
</html>

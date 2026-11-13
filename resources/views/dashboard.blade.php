@php
    $esAdmin = Auth::user()->esAdministrador();
    $interes = [
        'alto' => 'bg-green-50 text-green-700 ring-green-200',
        'medio' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'bajo' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $kpis = [
        ['titulo' => 'Colegios activos', 'valor' => $resumen['instituciones_activas'], 'nota' => 'Directorio B2B con código AMIE', 'icono' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6', 'color' => 'bg-blue-50 text-blue-600'],
        ['titulo' => 'Docentes vinculados', 'valor' => $resumen['docentes'], 'nota' => 'Contactos por institución', 'icono' => 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 014-4h3a4 4 0 014 4v2H9zm3-11a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'bg-slate-100 text-slate-600'],
        ['titulo' => $esAdmin ? 'Visitas del mes' : 'Mis visitas del mes', 'valor' => $resumen['visitas_mes'], 'nota' => 'Desde el '.now()->startOfMonth()->format('d/m/Y'), 'icono' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z', 'color' => 'bg-green-50 text-green-600'],
        ['titulo' => 'Interés alto', 'valor' => $resumen['interes_alto'], 'nota' => 'Visitas con alta probabilidad de adopción', 'icono' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'bg-brand-50 text-brand-600'],
    ];
@endphp

<x-app-layout>
    <x-page-header
        :title="$esAdmin ? 'Panel de Gestión Comercial' : '¡Hola, '.Auth::user()->name.'!'"
        :subtitle="$esAdmin ? 'Monitoreo del directorio B2B y de las visitas registradas por los asesores.' : 'Resumen de tus visitas comerciales a colegios y docentes.'">
        <x-slot name="actions">
            <span class="pill border border-slate-200 bg-white py-1.5 text-slate-600">
                <span class="h-2 w-2 rounded-full bg-green-500"></span> Servidor activo
            </span>
            @hasanyrole('Administrador|Asesor')
                <a href="{{ route('visitas.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Nueva visita
                </a>
            @endhasanyrole
        </x-slot>
    </x-page-header>

    @if (! Auth::user()->hasAnyRole(['Administrador', 'Asesor']))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Tu usuario aún no tiene un rol asignado. Pide al administrador que te asigne el rol de Asesor o Administrador.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <div class="card flex items-start justify-between gap-4 p-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $kpi['titulo'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $kpi['valor'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $kpi['nota'] }}</p>
                </div>
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $kpi['color'] }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icono'] }}"/></svg>
                </span>
            </div>
        @endforeach
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <div>
                <h2 class="font-bold text-slate-900">Últimas {{ $ultimasVisitas->count() ?: '' }} visitas registradas</h2>
                <p class="text-xs text-slate-500">{{ $esAdmin ? 'Visitas enviadas por los asesores desde la app móvil y el panel web.' : 'Tus visitas más recientes.' }}</p>
            </div>
            @hasanyrole('Administrador|Asesor')
                <a href="{{ route('visitas.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Ver todas</a>
            @endhasanyrole
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-y border-slate-100 bg-slate-50/70">
                    <tr>
                        <th class="th">Fecha</th>
                        <th class="th">Asesor</th>
                        <th class="th">Colegio</th>
                        <th class="th">Interés</th>
                        <th class="th text-right">Origen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ultimasVisitas as $visita)
                        <tr class="hover:bg-slate-50/60">
                            <td class="td whitespace-nowrap text-slate-600">{{ $visita->fecha_visita->format('Y-m-d') }}</td>
                            <td class="td whitespace-nowrap font-semibold text-slate-900">{{ $visita->user->name }}</td>
                            <td class="td">
                                <p class="font-semibold text-slate-900">{{ $visita->institucion->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $visita->docente?->nombre_completo }}</p>
                            </td>
                            <td class="td">
                                <span class="pill ring-1 {{ $interes[$visita->nivel_interes] ?? '' }}">{{ ucfirst($visita->nivel_interes) }}</span>
                            </td>
                            <td class="td text-right">
                                <span class="pill bg-green-50 text-green-700 ring-1 ring-green-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    {{ $visita->origen === 'movil' ? 'App móvil' : 'Web' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">Todavía no hay visitas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

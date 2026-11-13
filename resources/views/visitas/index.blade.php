@php
    $colores = ['bajo' => 'bg-gray-100 text-gray-700', 'medio' => 'bg-amber-100 text-amber-800', 'alto' => 'bg-green-100 text-green-800'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900">
                {{ Auth::user()->esAdministrador() ? 'Visitas de los asesores' : 'Mis visitas' }}
            </h2>
            <a href="{{ route('visitas.create') }}" class="btn-primary">
                + Registrar visita
            </a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto">
            <x-flash />

            <form method="GET" class="card mb-5 flex flex-wrap items-end gap-3 p-4">
                @if ($asesores->isNotEmpty())
                    <div>
                        <x-input-label for="f_asesor" value="Asesor" />
                        <select id="f_asesor" name="asesor_id" class="mt-1">
                            <option value="">Todos</option>
                            @foreach ($asesores as $asesor)
                                <option value="{{ $asesor->id }}" @selected(($filtros['asesor_id'] ?? '') == $asesor->id)>{{ $asesor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label for="f_inst" value="Institución" />
                    <select id="f_inst" name="institucion_id" class="mt-1 max-w-xs">
                        <option value="">Todas</option>
                        @foreach ($instituciones as $inst)
                            <option value="{{ $inst->id }}" @selected(($filtros['institucion_id'] ?? '') == $inst->id)>{{ $inst->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="f_nivel" value="Interés" />
                    <select id="f_nivel" name="nivel_interes" class="mt-1">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Visita::NIVELES_INTERES as $nivel)
                            <option value="{{ $nivel }}" @selected(($filtros['nivel_interes'] ?? '') === $nivel)>{{ ucfirst($nivel) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="f_desde" value="Desde" />
                    <x-text-input id="f_desde" type="date" name="desde" class="mt-1" :value="$filtros['desde'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="f_hasta" value="Hasta" />
                    <x-text-input id="f_hasta" type="date" name="hasta" class="mt-1" :value="$filtros['hasta'] ?? ''" />
                </div>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Fecha</th>
                            <th class="px-5 py-3.5">Asesor</th>
                            <th class="px-5 py-3.5">Institución</th>
                            <th class="px-5 py-3.5">Docente</th>
                            <th class="px-5 py-3.5">Interés</th>
                            <th class="px-5 py-3.5">Estado docente</th>
                            <th class="px-5 py-3.5">Origen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($visitas as $visita)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <a href="{{ route('visitas.show', $visita) }}" class="text-indigo-600 hover:underline">{{ $visita->fecha_visita->format('d/m/Y H:i') }}</a>
                                </td>
                                <td class="px-5 py-3.5">{{ $visita->user->name }}</td>
                                <td class="px-5 py-3.5">{{ $visita->institucion->nombre }}</td>
                                <td class="px-5 py-3.5">{{ $visita->docente?->nombre_completo ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colores[$visita->nivel_interes] ?? '' }}">{{ ucfirst($visita->nivel_interes) }}</span>
                                </td>
                                <td class="px-5 py-3.5">{{ ucfirst($visita->estado_adopcion) }}</td>
                                <td class="px-5 py-3.5">{{ $visita->origen === 'movil' ? 'App móvil' : 'Web' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-gray-500">No hay visitas registradas con estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $visitas->links() }}</div>
        </div>
    </div>
</x-app-layout>

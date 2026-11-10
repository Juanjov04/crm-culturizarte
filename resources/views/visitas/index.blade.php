@php
    $colores = ['bajo' => 'bg-gray-100 text-gray-700', 'medio' => 'bg-amber-100 text-amber-800', 'alto' => 'bg-green-100 text-green-800'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ Auth::user()->esAdministrador() ? 'Visitas de los asesores' : 'Mis visitas' }}
            </h2>
            <a href="{{ route('visitas.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Registrar visita
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
                @if ($asesores->isNotEmpty())
                    <div>
                        <x-input-label for="f_asesor" value="Asesor" />
                        <select id="f_asesor" name="asesor_id" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            @foreach ($asesores as $asesor)
                                <option value="{{ $asesor->id }}" @selected(($filtros['asesor_id'] ?? '') == $asesor->id)>{{ $asesor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label for="f_inst" value="Institución" />
                    <select id="f_inst" name="institucion_id" class="mt-1 border-gray-300 rounded-md shadow-sm max-w-xs">
                        <option value="">Todas</option>
                        @foreach ($instituciones as $inst)
                            <option value="{{ $inst->id }}" @selected(($filtros['institucion_id'] ?? '') == $inst->id)>{{ $inst->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="f_nivel" value="Interés" />
                    <select id="f_nivel" name="nivel_interes" class="mt-1 border-gray-300 rounded-md shadow-sm">
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

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Asesor</th>
                            <th class="px-4 py-3">Institución</th>
                            <th class="px-4 py-3">Docente</th>
                            <th class="px-4 py-3">Interés</th>
                            <th class="px-4 py-3">Estado docente</th>
                            <th class="px-4 py-3">Origen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($visitas as $visita)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('visitas.show', $visita) }}" class="text-indigo-600 hover:underline">{{ $visita->fecha_visita->format('d/m/Y H:i') }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $visita->user->name }}</td>
                                <td class="px-4 py-3">{{ $visita->institucion->nombre }}</td>
                                <td class="px-4 py-3">{{ $visita->docente?->nombre_completo ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colores[$visita->nivel_interes] ?? '' }}">{{ ucfirst($visita->nivel_interes) }}</span>
                                </td>
                                <td class="px-4 py-3">{{ ucfirst($visita->estado_adopcion) }}</td>
                                <td class="px-4 py-3">{{ $visita->origen === 'movil' ? 'App móvil' : 'Web' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay visitas registradas con estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $visitas->links() }}</div>
        </div>
    </div>
</x-app-layout>

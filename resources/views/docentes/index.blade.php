<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Docentes</h2>
            <a href="{{ route('docentes.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Nuevo docente
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="mb-4 flex flex-wrap gap-3">
                <x-text-input name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, cédula o área" class="w-full sm:w-80" />
                <select name="institucion_id" class="border-gray-300 rounded-md shadow-sm max-w-full">
                    <option value="">Todas las instituciones</option>
                    @foreach ($instituciones as $inst)
                        <option value="{{ $inst->id }}" @selected((string) $institucionId === (string) $inst->id)>{{ $inst->nombre }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Docente</th>
                            <th class="px-4 py-3">Cédula</th>
                            <th class="px-4 py-3">Área / asignatura</th>
                            <th class="px-4 py-3">Institución</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($docentes as $docente)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $docente->nombre_completo }}</td>
                                <td class="px-4 py-3">{{ $docente->cedula ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $docente->area_asignatura }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('instituciones.show', $docente->institucion) }}" class="text-indigo-600 hover:underline">{{ $docente->institucion->nombre }}</a>
                                    @unless ($docente->institucion->activo)
                                        <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="La institución está inactiva">Institución inactiva</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('docentes.edit', $docente) }}" class="text-indigo-600 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay docentes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $docentes->links() }}</div>
        </div>
    </div>
</x-app-layout>

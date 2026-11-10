<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Instituciones educativas</h2>
            <a href="{{ route('instituciones.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Nueva institución
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="mb-4 flex flex-wrap gap-3">
                <x-text-input name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, AMIE o ciudad" class="w-full sm:w-80" />
                <select name="estado" class="border-gray-300 rounded-md shadow-sm">
                    <option value="">Todas</option>
                    <option value="activas" @selected($estado === 'activas')>Activas</option>
                    <option value="inactivas" @selected($estado === 'inactivas')>Inactivas</option>
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Código AMIE</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Ciudad</th>
                            <th class="px-4 py-3 text-center">Docentes</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($instituciones as $institucion)
                            <tr>
                                <td class="px-4 py-3 font-mono">{{ $institucion->codigo_amie }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('instituciones.show', $institucion) }}" class="font-medium text-indigo-600 hover:underline">{{ $institucion->nombre }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $institucion->tipo }}</td>
                                <td class="px-4 py-3">{{ $institucion->ciudad }}</td>
                                <td class="px-4 py-3 text-center">{{ $institucion->docentes_count }}</td>
                                <td class="px-4 py-3"><x-estado-badge :activo="$institucion->activo" /></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('instituciones.edit', $institucion) }}" class="text-indigo-600 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay instituciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $instituciones->links() }}</div>
        </div>
    </div>
</x-app-layout>

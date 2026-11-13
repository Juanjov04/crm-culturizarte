<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900">Instituciones educativas</h2>
            <a href="{{ route('instituciones.create') }}" class="btn-primary">
                + Nueva institución
            </a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto">
            <x-flash />

            <form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-4">
                <x-text-input name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, AMIE o ciudad" class="w-full sm:w-80" />
                <select name="estado">
                    <option value="">Todas</option>
                    <option value="activas" @selected($estado === 'activas')>Activas</option>
                    <option value="inactivas" @selected($estado === 'inactivas')>Inactivas</option>
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Código AMIE</th>
                            <th class="px-5 py-3.5">Nombre</th>
                            <th class="px-5 py-3.5">Tipo</th>
                            <th class="px-5 py-3.5">Ciudad</th>
                            <th class="px-5 py-3.5 text-center">Docentes</th>
                            <th class="px-5 py-3.5">Estado</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($instituciones as $institucion)
                            <tr>
                                <td class="px-5 py-3.5 font-mono">{{ $institucion->codigo_amie }}</td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('instituciones.show', $institucion) }}" class="font-medium text-indigo-600 hover:underline">{{ $institucion->nombre }}</a>
                                </td>
                                <td class="px-5 py-3.5">{{ $institucion->tipo }}</td>
                                <td class="px-5 py-3.5">{{ $institucion->ciudad }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $institucion->docentes_count }}</td>
                                <td class="px-5 py-3.5"><x-estado-badge :activo="$institucion->activo" /></td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('instituciones.edit', $institucion) }}" class="text-indigo-600 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-gray-500">No hay instituciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $instituciones->links() }}</div>
        </div>
    </div>
</x-app-layout>

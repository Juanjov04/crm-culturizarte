<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900">Docentes</h2>
            <a href="{{ route('docentes.create') }}" class="btn-primary">
                + Nuevo docente
            </a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto">
            <x-flash />

            <form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-4">
                <x-text-input name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, cédula o área" class="w-full sm:w-80" />
                <select name="institucion_id" class="max-w-full">
                    <option value="">Todas las instituciones</option>
                    @foreach ($instituciones as $inst)
                        <option value="{{ $inst->id }}" @selected((string) $institucionId === (string) $inst->id)>{{ $inst->nombre }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Docente</th>
                            <th class="px-5 py-3.5">Cédula</th>
                            <th class="px-5 py-3.5">Área / asignatura</th>
                            <th class="px-5 py-3.5">Institución</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($docentes as $docente)
                            <tr>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $docente->nombre_completo }}</td>
                                <td class="px-5 py-3.5">{{ $docente->cedula ?: '—' }}</td>
                                <td class="px-5 py-3.5">{{ $docente->area_asignatura }}</td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('instituciones.show', $docente->institucion) }}" class="text-indigo-600 hover:underline">{{ $docente->institucion->nombre }}</a>
                                    @unless ($docente->institucion->activo)
                                        <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800" title="La institución está inactiva">Institución inactiva</span>
                                    @endunless
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('docentes.edit', $docente) }}" class="text-indigo-600 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-500">No hay docentes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $docentes->links() }}</div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $institucion->nombre }}</h2>
                <p class="text-sm text-gray-500">AMIE {{ $institucion->codigo_amie }} · {{ $institucion->tipo }} · {{ $institucion->ciudad }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-estado-badge :activo="$institucion->activo" />
                <a href="{{ route('instituciones.edit', $institucion) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if (! $institucion->activo && $institucion->docentes->isNotEmpty())
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                    Esta institución está inactiva y tiene {{ $institucion->docentes->count() }} docente(s) vinculado(s). No se podrán registrar visitas a estos docentes hasta reasignarlos.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Dirección</p>
                    <p class="mt-1 text-gray-900">{{ $institucion->direccion }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Contacto</p>
                    <p class="mt-1 text-gray-900">{{ $institucion->telefono ?: '—' }}</p>
                    <p class="text-gray-600 text-sm">{{ $institucion->email_contacto ?: '' }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Visitas registradas</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $institucion->visitas_count }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-800">Docentes vinculados ({{ $institucion->docentes->count() }})</h3>
                    @if ($institucion->activo)
                        <a href="{{ route('docentes.create', ['institucion_id' => $institucion->id]) }}" class="text-sm font-medium text-indigo-600 hover:underline">+ Agregar docente</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Docente</th>
                                <th class="px-4 py-3">Área / asignatura</th>
                                <th class="px-4 py-3">Teléfono</th>
                                <th class="px-4 py-3">Correo</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($institucion->docentes as $docente)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $docente->nombre_completo }}</td>
                                    <td class="px-4 py-3">{{ $docente->area_asignatura }}</td>
                                    <td class="px-4 py-3">{{ $docente->telefono ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $docente->email ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('docentes.edit', $docente) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aún no hay docentes vinculados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

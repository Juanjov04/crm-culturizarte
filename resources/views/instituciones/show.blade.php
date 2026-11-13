<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $institucion->nombre }}</h2>
                <p class="text-sm text-gray-500">AMIE {{ $institucion->codigo_amie }} · {{ $institucion->tipo }} · {{ $institucion->ciudad }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-estado-badge :activo="$institucion->activo" />
                <a href="{{ route('instituciones.edit', $institucion) }}" class="btn-secondary">Editar</a>
            </div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto space-y-6">
            <x-flash />

            @if (! $institucion->activo && $institucion->docentes->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
                    Esta institución está inactiva y tiene {{ $institucion->docentes->count() }} docente(s) vinculado(s). No se podrán registrar visitas a estos docentes hasta reasignarlos.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Dirección</p>
                    <p class="mt-1 text-gray-900">{{ $institucion->direccion }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Contacto</p>
                    <p class="mt-1 text-gray-900">{{ $institucion->telefono ?: '—' }}</p>
                    <p class="text-gray-600 text-sm">{{ $institucion->email_contacto ?: '' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Visitas registradas</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $institucion->visitas_count }}</p>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4">
                    <h3 class="font-bold text-slate-900">Docentes vinculados ({{ $institucion->docentes->count() }})</h3>
                    @if ($institucion->activo)
                        <a href="{{ route('docentes.create', ['institucion_id' => $institucion->id]) }}" class="text-sm font-medium text-indigo-600 hover:underline">+ Agregar docente</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5">Docente</th>
                                <th class="px-5 py-3.5">Área / asignatura</th>
                                <th class="px-5 py-3.5">Teléfono</th>
                                <th class="px-5 py-3.5">Correo</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($institucion->docentes as $docente)
                                <tr>
                                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ $docente->nombre_completo }}</td>
                                    <td class="px-5 py-3.5">{{ $docente->area_asignatura }}</td>
                                    <td class="px-5 py-3.5">{{ $docente->telefono ?: '—' }}</td>
                                    <td class="px-5 py-3.5">{{ $docente->email ?: '—' }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('docentes.edit', $docente) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-gray-500">Aún no hay docentes vinculados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

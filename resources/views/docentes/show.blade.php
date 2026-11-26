<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $docente->nombre_completo }}</h2>
                <p class="text-sm text-gray-500">{{ $docente->area_asignatura }} · {{ $docente->institucion->nombre }}</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('docentes.edit', $docente) }}" class="text-sm text-indigo-600 hover:underline">Editar</a>
                <a href="{{ route('docentes.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver al listado</a>
            </div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Cédula</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $docente->cedula ?: '—' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Contacto</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $docente->telefono ?: '—' }}</p>
                    <p class="text-sm text-gray-600">{{ $docente->email ?: '' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Muestras recibidas</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $muestras->sum('cantidad') }}</p>
                </div>
            </div>

            <div class="card overflow-x-auto">
                <h3 class="border-b border-slate-100 p-4 font-bold text-slate-900">Historial de muestras entregadas</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Fecha</th>
                            <th class="px-5 py-3.5">Texto</th>
                            <th class="px-5 py-3.5 text-center">Cantidad</th>
                            <th class="px-5 py-3.5">Asesor</th>
                            <th class="px-5 py-3.5 text-right">Visita</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($muestras as $muestra)
                            <tr>
                                <td class="px-5 py-3.5">{{ $muestra->visita->fecha_visita->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $muestra->producto->nombre_completo }}</td>
                                <td class="px-5 py-3.5 text-center">{{ $muestra->cantidad }}</td>
                                <td class="px-5 py-3.5">{{ $muestra->visita->user->name }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ route('visitas.show', $muestra->visita_id) }}" class="text-indigo-600 hover:underline">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray-500">El docente aún no ha recibido muestras.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

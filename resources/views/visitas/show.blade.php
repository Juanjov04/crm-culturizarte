<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Visita a {{ $visita->institucion->nombre }}</h2>
                <p class="text-sm text-gray-500">{{ $visita->fecha_visita->format('d/m/Y H:i') }} · registrada por {{ $visita->user->name }} desde {{ $visita->origen === 'movil' ? 'la app móvil' : 'el panel web' }}</p>
            </div>
            <a href="{{ route('visitas.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver al listado</a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-4xl mx-auto space-y-6">
            <x-flash />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Docente</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $visita->docente?->nombre_completo ?? '—' }}</p>
                    <p class="text-sm text-gray-600">{{ $visita->docente?->area_asignatura }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Nivel de interés</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ ucfirst($visita->nivel_interes) }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Estado frente a la adopción</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ ucfirst($visita->estado_adopcion) }}</p>
                </div>
            </div>

            <div class="card p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase text-gray-500">Acuerdos</h3>
                    <p class="mt-1 whitespace-pre-line text-gray-900">{{ $visita->acuerdos ?: 'Sin acuerdos registrados.' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold uppercase text-gray-500">Observaciones</h3>
                    <p class="mt-1 whitespace-pre-line text-gray-900">{{ $visita->observaciones ?: 'Sin observaciones.' }}</p>
                </div>
                @if ($visita->latitud && $visita->longitud)
                    <div>
                        <h3 class="text-sm font-semibold uppercase text-gray-500">Ubicación</h3>
                        <p class="mt-1 font-mono text-sm text-gray-700">{{ $visita->latitud }}, {{ $visita->longitud }}</p>
                    </div>
                @endif
                <p class="text-xs text-gray-400">ID de sincronización: {{ $visita->uuid_offline }}</p>
            </div>
        </div>
    </div>
</x-app-layout>

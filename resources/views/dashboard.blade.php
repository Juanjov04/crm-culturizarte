<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel CRM Culturizarte
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (! Auth::user()->hasAnyRole(['Administrador', 'Asesor']))
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Tu usuario aún no tiene un rol asignado. Pide al administrador que te asigne el rol de Asesor o Administrador.
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Instituciones activas</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $resumen['instituciones_activas'] }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Docentes</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $resumen['docentes'] }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Visitas este mes</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $resumen['visitas_mes'] }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-500">Visitas con interés alto</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $resumen['interes_alto'] }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-800">Últimas visitas</h3>
                    @hasanyrole('Administrador|Asesor')
                        <a href="{{ route('visitas.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Ver todas</a>
                    @endhasanyrole
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($ultimasVisitas as $visita)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ $visita->institucion->nombre }}</p>
                                <p class="text-gray-500">{{ $visita->docente?->nombre_completo }} · {{ $visita->user->name }}</p>
                            </div>
                            <span class="text-gray-500">{{ $visita->fecha_visita->format('d/m/Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-gray-500">Todavía no hay visitas registradas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

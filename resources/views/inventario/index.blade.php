<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900">Inventario de muestras por asesor</h2>
            <a href="{{ route('productos.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Ver catálogo y stock de bodega</a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto space-y-6">
            <x-flash />

            {{-- Asignación de muestras de bodega a un asesor --}}
            <form method="POST" action="{{ route('inventario.store') }}" class="card p-6">
                @csrf
                <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-brand-600">Asignar muestras desde bodega</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <x-input-label for="asesor_id" value="Asesor *" />
                        <select id="asesor_id" name="asesor_id" class="mt-1 block w-full" required>
                            <option value="">Seleccione</option>
                            @foreach ($asesores as $asesor)
                                <option value="{{ $asesor->id }}" @selected((string) old('asesor_id') === (string) $asesor->id)>{{ $asesor->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('asesor_id')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="producto_id" value="Texto escolar *" />
                        <select id="producto_id" name="producto_id" class="mt-1 block w-full" required>
                            <option value="">Seleccione</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}" @selected((string) old('producto_id') === (string) $producto->id) @disabled($producto->stock_actual === 0)>
                                    {{ $producto->titulo }} ({{ $producto->codigo_isbn }}) — bodega: {{ $producto->stock_actual }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('producto_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="cantidad" value="Cantidad *" />
                        <x-text-input id="cantidad" name="cantidad" type="number" min="1" class="mt-1 block w-full" :value="old('cantidad', 1)" required />
                        <x-input-error :messages="$errors->get('cantidad')" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <x-primary-button>Asignar</x-primary-button>
                </div>
            </form>

            <div class="card">
                <form method="GET" class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
                    <h3 class="mr-auto font-bold text-slate-900">Stock en manos de asesores</h3>
                    <select name="asesor_id">
                        <option value="">Todos los asesores</option>
                        @foreach ($asesores as $asesor)
                            <option value="{{ $asesor->id }}" @selected((string) $asesorId === (string) $asesor->id)>{{ $asesor->name }}</option>
                        @endforeach
                    </select>
                    <x-secondary-button type="submit">Filtrar</x-secondary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5">Asesor</th>
                                <th class="px-5 py-3.5">Texto</th>
                                <th class="px-5 py-3.5">Área</th>
                                <th class="px-5 py-3.5 text-center">Unidades</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($inventario as $item)
                                <tr>
                                    <td class="px-5 py-3.5">{{ $item->user->name }}</td>
                                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ $item->producto->titulo }}</td>
                                    <td class="px-5 py-3.5">{{ $item->producto->area }}</td>
                                    <td class="px-5 py-3.5 text-center font-semibold">{{ $item->cantidad }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-gray-500">Ningún asesor tiene muestras asignadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 class="border-b border-slate-100 p-4 font-bold text-slate-900">Últimas asignaciones</h3>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($asignaciones as $a)
                        <li class="flex flex-wrap justify-between gap-2 px-5 py-3">
                            <span><strong>{{ $a->cantidad }}</strong> × {{ $a->producto->titulo }} → {{ $a->asesor->name }}</span>
                            <span class="text-slate-400">{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->asignadoPor->name }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-gray-500">Aún no hay asignaciones.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>

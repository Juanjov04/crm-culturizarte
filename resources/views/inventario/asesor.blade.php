<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Mi inventario de muestras</h2>
    </x-slot>

    <div>
        <div class="max-w-5xl mx-auto">
            <x-flash />

            <div class="card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Texto</th>
                            <th class="px-5 py-3.5">Serie</th>
                            <th class="px-5 py-3.5">Área</th>
                            <th class="px-5 py-3.5">Grado</th>
                            <th class="px-5 py-3.5 text-center">Unidades</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($inventario as $item)
                            <tr>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $item->producto->titulo }}@if ($item->producto->edicion)<span class="block text-xs font-normal text-slate-500">{{ $item->producto->edicion }}</span>@endif</td>
                                <td class="px-5 py-3.5">{{ $item->producto->serie }}</td>
                                <td class="px-5 py-3.5">{{ $item->producto->area }}</td>
                                <td class="px-5 py-3.5">{{ $item->producto->nivel_educativo }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span @class(['font-semibold', 'text-red-600' => $item->cantidad === 0])>{{ $item->cantidad }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-500">Aún no tienes muestras asignadas. Solicítalas al administrador.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">Las muestras solo se pueden entregar si tienes unidades disponibles; cada entrega descuenta de tu inventario.</p>
        </div>
    </div>
</x-app-layout>

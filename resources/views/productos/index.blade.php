<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-slate-900">Catálogo de textos escolares</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('inventario.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Asignar muestras a asesores</a>
                <a href="{{ route('productos.create') }}" class="btn-primary">+ Nuevo texto</a>
            </div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto">
            <x-flash />

            <form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-4">
                <x-text-input name="buscar" value="{{ $buscar }}" placeholder="Buscar por título, código o área" class="w-full sm:w-80" />
                <select name="serie">
                    <option value="">Todas las series</option>
                    @foreach (\App\Models\Producto::SERIES as $s)
                        <option value="{{ $s }}" @selected($serie === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </form>

            <div class="card overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">SKU / ISBN</th>
                            <th class="px-5 py-3.5">Título</th>
                            <th class="px-5 py-3.5">Serie</th>
                            <th class="px-5 py-3.5">Área</th>
                            <th class="px-5 py-3.5">Grado</th>
                            <th class="px-5 py-3.5 text-center">Bodega</th>
                            <th class="px-5 py-3.5 text-center whitespace-nowrap">Con asesores</th>
                            <th class="px-5 py-3.5">Estado</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="px-5 py-3.5 font-mono whitespace-nowrap">{{ $producto->codigo_isbn }}</td>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $producto->titulo }}</td>
                                <td class="px-5 py-3.5">{{ $producto->serie }}</td>
                                <td class="px-5 py-3.5">{{ $producto->area }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap">{{ $producto->nivel_educativo }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span @class(['font-semibold', 'text-red-600' => $producto->stock_actual === 0])>{{ $producto->stock_actual }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">{{ (int) $producto->stock_asesores }}</td>
                                <td class="px-5 py-3.5"><x-estado-badge :activo="$producto->activo" /></td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('productos.edit', $producto) }}" class="text-indigo-600 hover:underline">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-gray-500">No hay textos en el catálogo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $productos->links() }}</div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    @vite('resources/js/indicadores.js')
@endpush

<x-app-layout>
    <x-page-header title="Indicadores de gestión comercial"
        subtitle="Adopción, retención (churn rate) y entrega de muestras, con los datos sincronizados por los asesores.">
        <x-slot name="actions">
            <a href="{{ route('indicadores.imprimir', $filtros) }}" target="_blank" class="btn-secondary">Exportar PDF</a>
            <div x-data="{ abierto: false }" class="relative">
                <button type="button" @click="abierto = ! abierto" @click.outside="abierto = false" class="btn-primary">
                    Exportar Excel / Power BI ▾
                </button>
                <div x-show="abierto" x-transition style="display: none"
                    class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm shadow-lg">
                    <a href="{{ route('indicadores.exportar', ['tipo' => 'visitas'] + $filtros) }}" class="block px-4 py-2.5 hover:bg-slate-50">Visitas del período (CSV)</a>
                    <a href="{{ route('indicadores.exportar', ['tipo' => 'colegios'] + $filtros) }}" class="block px-4 py-2.5 hover:bg-slate-50">Estado por colegio (CSV)</a>
                    <a href="{{ route('indicadores.exportar', ['tipo' => 'muestras'] + $filtros) }}" class="block px-4 py-2.5 hover:bg-slate-50">Muestras entregadas (CSV)</a>
                    <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">Se abren en Excel y se importan en Power BI (Obtener datos → Texto/CSV).</p>
                </div>
            </div>
        </x-slot>
    </x-page-header>

    {{-- Filtros (RF-04): fechas, zona, asesor y serie de libros --}}
    <form method="GET" class="card mb-6 grid grid-cols-2 items-end gap-4 p-5 md:grid-cols-3 lg:grid-cols-6">
        <div>
            <x-input-label for="desde" value="Desde" />
            <x-text-input id="desde" name="desde" type="date" class="mt-1 block w-full" :value="$filtros['desde']" />
        </div>
        <div>
            <x-input-label for="hasta" value="Hasta" />
            <x-text-input id="hasta" name="hasta" type="date" class="mt-1 block w-full" :value="$filtros['hasta']" />
            <x-input-error :messages="$errors->get('hasta')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="provincia" value="Provincia (zona)" />
            <select id="provincia" name="provincia" class="mt-1 block w-full">
                <option value="">Todas</option>
                @foreach ($provincias as $p)
                    <option value="{{ $p }}" @selected(($filtros['provincia'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="asesor_id" value="Asesor" />
            <select id="asesor_id" name="asesor_id" class="mt-1 block w-full">
                <option value="">Todos</option>
                @foreach ($asesores as $a)
                    <option value="{{ $a->id }}" @selected((string) ($filtros['asesor_id'] ?? '') === (string) $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="serie" value="Serie de libros" />
            <select id="serie" name="serie" class="mt-1 block w-full">
                <option value="">Todas</option>
                @foreach ($series as $s)
                    <option value="{{ $s }}" @selected(($filtros['serie'] ?? '') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <x-primary-button class="flex-1 justify-center">Aplicar</x-primary-button>
            <a href="{{ route('indicadores.index') }}" class="btn-secondary" title="Quitar filtros">✕</a>
        </div>
        <p class="col-span-full text-xs text-slate-500">La serie de libros filtra las muestras entregadas; el resto de indicadores se calcula sobre las visitas.</p>
    </form>

    @include('indicadores._contenido')
</x-app-layout>

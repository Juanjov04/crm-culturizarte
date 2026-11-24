@php($series = \App\Models\Producto::SERIES)

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="codigo_isbn" value="Código SKU / ISBN *" />
        <x-text-input id="codigo_isbn" name="codigo_isbn" class="mt-1 block w-full uppercase" maxlength="20"
            :value="old('codigo_isbn', $producto->codigo_isbn)" required />
        <x-input-error :messages="$errors->get('codigo_isbn')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="serie" value="Serie *" />
        <select id="serie" name="serie" class="mt-1 block w-full" required>
            @foreach ($series as $serie)
                <option value="{{ $serie }}" @selected(old('serie', $producto->serie) === $serie)>{{ $serie }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('serie')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="titulo" value="Título del texto *" />
        <x-text-input id="titulo" name="titulo" class="mt-1 block w-full" :value="old('titulo', $producto->titulo)" required />
        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="area" value="Área / asignatura compatible *" />
        <x-text-input id="area" name="area" class="mt-1 block w-full" list="areas-conocidas" placeholder="Ej. Lengua y Literatura"
            :value="old('area', $producto->area)" required />
        <datalist id="areas-conocidas">
            @foreach ($areas as $area)
                <option value="{{ $area }}"></option>
            @endforeach
        </datalist>
        <p class="mt-1 text-xs text-slate-500">Solo se podrá entregar como muestra a docentes de esta área.</p>
        <x-input-error :messages="$errors->get('area')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nivel_educativo" value="Grado / nivel *" />
        <x-text-input id="nivel_educativo" name="nivel_educativo" class="mt-1 block w-full" placeholder="Ej. 5.º EGB"
            :value="old('nivel_educativo', $producto->nivel_educativo)" required />
        <x-input-error :messages="$errors->get('nivel_educativo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="stock_actual" value="Stock disponible en bodega *" />
        <x-text-input id="stock_actual" name="stock_actual" type="number" min="0" class="mt-1 block w-full"
            :value="old('stock_actual', $producto->stock_actual)" required />
        <p class="mt-1 text-xs text-slate-500">Stock referencial: se descuenta al asignar muestras a los asesores.</p>
        <x-input-error :messages="$errors->get('stock_actual')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="activo" value="0">
        <input id="activo" name="activo" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
            @checked(old('activo', $producto->activo ?? true))>
        <label for="activo" class="text-sm text-gray-700">Disponible en el catálogo</label>
    </div>
</div>

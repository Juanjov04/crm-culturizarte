@php($tipos = \App\Models\Institucion::TIPOS)

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="codigo_amie" value="Código AMIE *" />
        <x-text-input id="codigo_amie" name="codigo_amie" class="mt-1 block w-full uppercase" maxlength="20"
            :value="old('codigo_amie', $institucion->codigo_amie)" required />
        <x-input-error :messages="$errors->get('codigo_amie')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="tipo" value="Tipo de sostenimiento *" />
        <select id="tipo" name="tipo" class="mt-1 block w-full" required>
            @foreach ($tipos as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo', $institucion->tipo ?? 'Particular') === $tipo)>{{ $tipo }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="nombre" value="Nombre de la institución *" />
        <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $institucion->nombre)" required />
        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ciudad" value="Ciudad *" />
        <x-text-input id="ciudad" name="ciudad" class="mt-1 block w-full" :value="old('ciudad', $institucion->ciudad)" required />
        <x-input-error :messages="$errors->get('ciudad')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="telefono" value="Teléfono" />
        <x-text-input id="telefono" name="telefono" class="mt-1 block w-full" :value="old('telefono', $institucion->telefono)" />
        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="direccion" value="Dirección *" />
        <x-text-input id="direccion" name="direccion" class="mt-1 block w-full" :value="old('direccion', $institucion->direccion)" required />
        <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email_contacto" value="Correo de contacto" />
        <x-text-input id="email_contacto" name="email_contacto" type="email" class="mt-1 block w-full" :value="old('email_contacto', $institucion->email_contacto)" />
        <x-input-error :messages="$errors->get('email_contacto')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="activo" value="0">
        <input id="activo" name="activo" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
            @checked(old('activo', $institucion->activo ?? true))>
        <label for="activo" class="text-sm text-gray-700">Institución activa</label>
    </div>
</div>

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

    {{-- Observación del jurado: provincia y ciudad se eligen de un menú (sin escribir). --}}
    <div class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-2"
        x-data="{
            provincias: @js(\App\Support\Ecuador::provincias()),
            provincia: @js(old('provincia', $institucion->provincia)),
            ciudad: @js(old('ciudad', $institucion->ciudad)),
            get ciudades() { return this.provincias[this.provincia] ?? []; },
        }">
        <div>
            <x-input-label for="provincia" value="Provincia *" />
            <select id="provincia" name="provincia" x-model="provincia" @change="ciudad = ''" class="mt-1 block w-full" required>
                <option value="">Seleccione la provincia</option>
                <template x-for="p in Object.keys(provincias)" :key="p">
                    <option :value="p" x-text="p" :selected="p === provincia"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('provincia')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="ciudad" value="Ciudad (cantón) *" />
            <select id="ciudad" name="ciudad" x-model="ciudad" :disabled="!provincia" class="mt-1 block w-full disabled:bg-gray-100" required>
                <option value="" x-text="provincia ? 'Seleccione la ciudad' : 'Primero elija la provincia'"></option>
                <template x-for="c in ciudades" :key="c">
                    <option :value="c" x-text="c" :selected="c === ciudad"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('ciudad')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="telefono" value="Teléfono *" />
        <x-text-input id="telefono" name="telefono" class="mt-1 block w-full" maxlength="10" minlength="9" inputmode="numeric" pattern="[0-9]{9,10}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')" :value="old('telefono', $institucion->telefono)" required />
        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="direccion" value="Dirección *" />
        <x-text-input id="direccion" name="direccion" class="mt-1 block w-full" :value="old('direccion', $institucion->direccion)" required />
        <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email_contacto" value="Correo de contacto *" />
        <x-text-input id="email_contacto" name="email_contacto" type="email" class="mt-1 block w-full" :value="old('email_contacto', $institucion->email_contacto)" required />
        <x-input-error :messages="$errors->get('email_contacto')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="activo" value="0">
        <input id="activo" name="activo" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
            @checked(old('activo', $institucion->activo ?? true))>
        <label for="activo" class="text-sm text-gray-700">Institución activa</label>
    </div>
</div>

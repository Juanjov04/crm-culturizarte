<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="institucion_id" value="Institución *" />
        <select id="institucion_id" name="institucion_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <option value="">Seleccione una institución activa</option>
            @foreach ($instituciones as $inst)
                <option value="{{ $inst->id }}" @selected((string) old('institucion_id', $docente->institucion_id) === (string) $inst->id)>
                    {{ $inst->nombre }} ({{ $inst->codigo_amie }}){{ $inst->activo ? '' : ' — inactiva' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('institucion_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nombres" value="Nombres *" />
        <x-text-input id="nombres" name="nombres" class="mt-1 block w-full" :value="old('nombres', $docente->nombres)" required />
        <x-input-error :messages="$errors->get('nombres')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="apellidos" value="Apellidos *" />
        <x-text-input id="apellidos" name="apellidos" class="mt-1 block w-full" :value="old('apellidos', $docente->apellidos)" required />
        <x-input-error :messages="$errors->get('apellidos')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="cedula" value="Cédula" />
        <x-text-input id="cedula" name="cedula" class="mt-1 block w-full" maxlength="10" inputmode="numeric" :value="old('cedula', $docente->cedula)" />
        <x-input-error :messages="$errors->get('cedula')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="area_asignatura" value="Área / asignatura *" />
        <x-text-input id="area_asignatura" name="area_asignatura" class="mt-1 block w-full" placeholder="Ej. Lengua y Literatura" :value="old('area_asignatura', $docente->area_asignatura)" required />
        <x-input-error :messages="$errors->get('area_asignatura')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="telefono" value="Teléfono" />
        <x-text-input id="telefono" name="telefono" class="mt-1 block w-full" :value="old('telefono', $docente->telefono)" />
        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Correo" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $docente->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
</div>

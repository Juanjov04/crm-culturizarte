@php
    $niveles = \App\Models\Visita::NIVELES_INTERES;
    $estados = \App\Models\Visita::ESTADOS_ADOPCION;
    $catalogo = $instituciones->mapWithKeys(fn ($i) => [
        $i->id => $i->docentes->map(fn ($d) => ['id' => $d->id, 'nombre' => $d->nombre_completo.' — '.$d->area_asignatura])->values(),
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Registrar visita comercial</h2>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto">
            <form method="POST" action="{{ route('visitas.store') }}" class="card p-6 space-y-6"
                x-data="{
                    catalogo: @js($catalogo),
                    institucion: '{{ old('institucion_id') }}',
                    docente: '{{ old('docente_id') }}',
                    get docentes() { return this.catalogo[this.institucion] ?? []; },
                }">
                @csrf

                <fieldset class="space-y-4">
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-600">1. Institución y docente</legend>

                    <div>
                        <x-input-label for="institucion_id" value="Institución *" />
                        <select id="institucion_id" name="institucion_id" x-model="institucion" @change="docente = ''"
                            class="mt-1 block w-full" required>
                            <option value="">Seleccione una institución</option>
                            @foreach ($instituciones as $inst)
                                <option value="{{ $inst->id }}">{{ $inst->nombre }} ({{ $inst->codigo_amie }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('institucion_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="docente_id" value="Docente visitado *" />
                        <select id="docente_id" name="docente_id" x-model="docente" :disabled="!institucion"
                            class="mt-1 block w-full disabled:bg-gray-100" required>
                            <option value="" x-text="institucion ? (docentes.length ? 'Seleccione un docente' : 'La institución no tiene docentes') : 'Primero elija la institución'"></option>
                            <template x-for="d in docentes" :key="d.id">
                                <option :value="d.id" x-text="d.nombre" :selected="String(d.id) === String(docente)"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('docente_id')" class="mt-2" />
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-600">2. Resultado de la visita</legend>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="fecha_visita" value="Fecha y hora *" />
                            <x-text-input id="fecha_visita" name="fecha_visita" type="datetime-local" class="mt-1 block w-full"
                                :value="old('fecha_visita', now()->format('Y-m-d\TH:i'))" :max="now()->format('Y-m-d\TH:i')" required />
                            <x-input-error :messages="$errors->get('fecha_visita')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="nivel_interes" value="Nivel de interés *" />
                            <select id="nivel_interes" name="nivel_interes" class="mt-1 block w-full" required>
                                @foreach ($niveles as $nivel)
                                    <option value="{{ $nivel }}" @selected(old('nivel_interes', 'medio') === $nivel)>{{ ucfirst($nivel) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('nivel_interes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="estado_adopcion" value="Estado del docente *" />
                            <select id="estado_adopcion" name="estado_adopcion" class="mt-1 block w-full" required>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado }}" @selected(old('estado_adopcion', 'prospecto') === $estado)>{{ ucfirst($estado) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('estado_adopcion')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="acuerdos" value="Acuerdos alcanzados" />
                        <textarea id="acuerdos" name="acuerdos" rows="3" class="mt-1 block w-full">{{ old('acuerdos') }}</textarea>
                        <x-input-error :messages="$errors->get('acuerdos')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <textarea id="observaciones" name="observaciones" rows="3" class="mt-1 block w-full">{{ old('observaciones') }}</textarea>
                        <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                    </div>
                </fieldset>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('visitas.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Registrar visita</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

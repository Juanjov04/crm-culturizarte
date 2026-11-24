@php
    $niveles = \App\Models\Visita::NIVELES_INTERES;
    $estados = \App\Models\Visita::ESTADOS_ADOPCION;
    $catalogo = $instituciones->mapWithKeys(fn ($i) => [
        $i->id => $i->docentes->map(fn ($d) => [
            'id' => $d->id,
            'nombre' => $d->nombre_completo.' — '.$d->area_asignatura,
            'area' => \App\Models\Producto::normalizarArea($d->area_asignatura),
        ])->values(),
    ]);
    $muestrasPrevias = collect(old('muestras', []))->values();
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
                    productos: @js($productos),
                    institucion: '{{ old('institucion_id') }}',
                    docente: '{{ old('docente_id') }}',
                    muestras: @js($muestrasPrevias),
                    get docentes() { return this.catalogo[this.institucion] ?? []; },
                    get areaDocente() { return (this.docentes.find(d => String(d.id) === String(this.docente)) ?? {}).area ?? null; },
                    get compatibles() { return this.productos.filter(p => p.area === this.areaDocente); },
                    producto(id) { return this.productos.find(p => String(p.id) === String(id)); },
                    agregar() { this.muestras.push({ producto_id: '', formato: 'fisica', cantidad: 1 }); },
                }">
                @csrf

                <fieldset class="space-y-4">
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-600">1. Institución y docente</legend>

                    <div>
                        <x-input-label for="institucion_id" value="Institución *" />
                        <select id="institucion_id" name="institucion_id" x-model="institucion" @change="docente = ''; muestras = []"
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
                        <select id="docente_id" name="docente_id" x-model="docente" :disabled="!institucion" @change="muestras = []"
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
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-600">2. Resultado y acuerdos</legend>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            {{-- Observación del jurado: la visita se registra con la fecha y hora actual. --}}
                            <x-input-label value="Fecha y hora" />
                            <p class="mt-1 flex h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">
                                {{ now()->format('d/m/Y H:i') }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">Se registra automáticamente al guardar.</p>
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

                {{-- Sprint 5 (RF-03): asignación de muestras al docente --}}
                <fieldset class="space-y-4">
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-600">3. Asignación de muestras</legend>

                    <p x-show="!docente" class="text-sm text-slate-500">Elija primero el docente para ver los textos compatibles con su área.</p>
                    <p x-show="docente && compatibles.length === 0" class="text-sm text-amber-700">No hay textos del catálogo para el área de este docente.</p>

                    <template x-for="(m, i) in muestras" :key="i">
                        <div class="grid grid-cols-12 items-start gap-3 rounded-xl border border-slate-200 p-3">
                            <div class="col-span-12 sm:col-span-6">
                                <select :name="`muestras[${i}][producto_id]`" x-model="m.producto_id" class="block w-full" required>
                                    <option value="">Seleccione el texto</option>
                                    <template x-for="p in compatibles" :key="p.id">
                                        <option :value="p.id" x-text="`${p.titulo} (stock: ${p.stock})`" :selected="String(p.id) === String(m.producto_id)"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-slate-500" x-text="producto(m.producto_id)?.detalle ?? ''"></p>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <select :name="`muestras[${i}][formato]`" x-model="m.formato" class="block w-full">
                                    <option value="fisica">Física</option>
                                    <option value="digital">Digital</option>
                                </select>
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <input type="number" min="1" :max="m.formato === 'fisica' ? (producto(m.producto_id)?.stock || 1) : 100"
                                    :name="`muestras[${i}][cantidad]`" x-model="m.cantidad" class="block w-full" required>
                            </div>
                            <button type="button" @click="muestras.splice(i, 1)" class="col-span-2 sm:col-span-1 py-2 text-slate-400 hover:text-red-600" title="Quitar">✕</button>
                            <p x-show="m.formato === 'fisica' && producto(m.producto_id) && producto(m.producto_id).stock === 0"
                                class="col-span-12 text-xs text-red-600">No tienes stock de este texto: solo puedes entregarlo en formato digital.</p>
                        </div>
                    </template>

                    @foreach ($errors->get('muestras*') as $mensajes)
                        <x-input-error :messages="$mensajes" />
                    @endforeach

                    <button type="button" @click="agregar()" x-show="docente && compatibles.length > 0"
                        class="text-sm font-semibold text-indigo-600 hover:underline">+ Agregar muestra</button>
                </fieldset>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('visitas.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Registrar visita</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

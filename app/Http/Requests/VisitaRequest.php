<?php

namespace App\Http\Requests;

use App\Models\MuestraEntregada;
use App\Models\Visita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 4 - Validación del formulario de visitas comerciales (RF-01) y,
 * desde el Sprint 5, de las muestras entregadas (RF-03).
 * La usan tanto el panel web como la API de la app móvil.
 */
class VisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid_offline' => ['nullable', 'uuid'],
            'institucion_id' => [
                'required', 'integer',
                Rule::exists('institucions', 'id')->where('activo', true),
            ],
            // El docente es obligatorio y debe pertenecer a la institución seleccionada.
            'docente_id' => [
                'required', 'integer',
                Rule::exists('docentes', 'id')->where('institucion_id', (int) $this->input('institucion_id')),
            ],
            // Observación del jurado: la visita se registra con la fecha y hora actual.
            // Si no se envía, el servidor usa la hora actual; si se envía, solo se
            // aceptan unos minutos de margen por el llenado del formulario.
            'fecha_visita' => [
                'nullable', 'date',
                'after_or_equal:'.now()->subMinutes(Visita::MARGEN_FECHA_MINUTOS)->toDateTimeString(),
                'before_or_equal:'.now()->addMinute()->toDateTimeString(),
            ],
            'nivel_interes' => ['required', Rule::in(Visita::NIVELES_INTERES)],
            'estado_adopcion' => ['required', Rule::in(Visita::ESTADOS_ADOPCION)],
            'acuerdos' => ['nullable', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],

            // Sprint 5 (RF-03): muestras entregadas al docente en la visita.
            'muestras' => ['nullable', 'array', 'max:20'],
            'muestras.*.producto_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('productos', 'id')->where('activo', true),
            ],
            'muestras.*.formato' => ['required', Rule::in(MuestraEntregada::FORMATOS)],
            'muestras.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'institucion_id.exists' => 'La institución no existe o está inactiva.',
            'docente_id.exists' => 'El docente seleccionado no pertenece a la institución.',
            'fecha_visita.after_or_equal' => 'La visita se registra con la fecha y hora actual; no se aceptan fechas ni horas anteriores.',
            'fecha_visita.before_or_equal' => 'La fecha de la visita no puede ser posterior a la fecha actual.',
            'muestras.*.producto_id.exists' => 'El texto seleccionado no existe o no está disponible.',
            'muestras.*.producto_id.distinct' => 'El mismo texto se agregó más de una vez.',
        ];
    }

    public function attributes(): array
    {
        return [
            'institucion_id' => 'institución',
            'docente_id' => 'docente',
            'fecha_visita' => 'fecha de la visita',
            'nivel_interes' => 'nivel de interés',
            'estado_adopcion' => 'estado del docente frente a la adopción',
            'muestras.*.producto_id' => 'texto escolar',
            'muestras.*.formato' => 'formato de la muestra',
            'muestras.*.cantidad' => 'cantidad',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Visita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 4 - Validación del formulario de visitas comerciales (RF-01).
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
            // La fecha de la visita no puede ser posterior a la fecha actual.
            'fecha_visita' => ['required', 'date', 'before_or_equal:now'],
            'nivel_interes' => ['required', Rule::in(Visita::NIVELES_INTERES)],
            'estado_adopcion' => ['required', Rule::in(Visita::ESTADOS_ADOPCION)],
            'acuerdos' => ['nullable', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'institucion_id.exists' => 'La institución no existe o está inactiva.',
            'docente_id.exists' => 'El docente seleccionado no pertenece a la institución.',
            'fecha_visita.before_or_equal' => 'La fecha de la visita no puede ser posterior a la fecha actual.',
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
        ];
    }
}

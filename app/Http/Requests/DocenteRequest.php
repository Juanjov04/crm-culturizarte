<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * RF-05: un docente debe estar asociado a una institución activa.
     */
    public function rules(): array
    {
        $docente = $this->route('docente');

        return [
            'institucion_id' => [
                'required',
                Rule::exists('institucions', 'id')->where('activo', true),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'cedula' => [
                'nullable', 'digits:10',
                Rule::unique('docentes', 'cedula')->ignore($docente?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'area_asignatura' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'institucion_id.exists' => 'El docente debe asignarse a una institución activa.',
            'cedula.unique' => 'Ya existe un docente con esta cédula.',
        ];
    }

    public function attributes(): array
    {
        return [
            'institucion_id' => 'institución',
            'area_asignatura' => 'área / asignatura',
            'cedula' => 'cédula',
            'telefono' => 'teléfono',
        ];
    }
}

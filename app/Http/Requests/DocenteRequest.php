<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
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
            // Observaciones del jurado: todos los datos son obligatorios y la
            // cédula debe ser ecuatoriana válida (solo números).
            'cedula' => [
                'required', 'digits:10', new CedulaEcuatoriana,
                Rule::unique('docentes', 'cedula')->ignore($docente?->id),
            ],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['required', 'regex:/^[0-9]{9,10}$/'],
            'area_asignatura' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'institucion_id.exists' => 'El docente debe asignarse a una institución activa.',
            'cedula.unique' => 'Ya existe un docente con esta cédula.',
            'cedula.digits' => 'La cédula debe tener 10 números, sin letras ni símbolos.',
            'telefono.regex' => 'El teléfono debe tener 9 o 10 números (fijo o celular), sin letras ni símbolos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'institucion_id' => 'institución',
            'area_asignatura' => 'área / asignatura',
            'cedula' => 'cédula',
            'telefono' => 'teléfono',
            'email' => 'correo',
        ];
    }
}

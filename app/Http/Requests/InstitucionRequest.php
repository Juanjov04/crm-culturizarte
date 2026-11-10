<?php

namespace App\Http\Requests;

use App\Models\Institucion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_amie' => strtoupper(trim((string) $this->codigo_amie)),
            'activo' => $this->boolean('activo'),
        ]);
    }

    /**
     * RF-05: todo colegio debe tener un código identificador único a nivel nacional (AMIE).
     */
    public function rules(): array
    {
        $institucion = $this->route('institucion');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo_amie' => [
                'required', 'string', 'max:20', 'regex:/^[0-9A-Z]+$/',
                Rule::unique('institucions', 'codigo_amie')->ignore($institucion?->id),
            ],
            'tipo' => ['required', Rule::in(Institucion::TIPOS)],
            'ciudad' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email_contacto' => ['nullable', 'email', 'max:255'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_amie.unique' => 'Ya existe una institución registrada con este código AMIE.',
            'codigo_amie.regex' => 'El código AMIE solo puede contener letras y números.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo_amie' => 'código AMIE',
            'email_contacto' => 'correo de contacto',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
        ];
    }
}

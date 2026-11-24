<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 5 - Asignación de muestras de bodega al inventario de un asesor.
 */
class AsignacionStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asesor_id' => [
                'required', 'integer',
                // Solo se asigna stock a usuarios con rol Asesor.
                Rule::exists('model_has_roles', 'model_id')->where(fn ($q) => $q
                    ->where('model_type', \App\Models\User::class)
                    ->whereIn('role_id', fn ($r) => $r->select('id')->from('roles')->where('name', 'Asesor'))),
            ],
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asesor_id.exists' => 'El usuario seleccionado no es un asesor comercial.',
            'producto_id.exists' => 'El texto no existe o está inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'asesor_id' => 'asesor',
            'producto_id' => 'texto escolar',
        ];
    }
}

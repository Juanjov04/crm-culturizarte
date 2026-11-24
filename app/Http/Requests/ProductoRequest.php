<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 5 - Catálogo de textos escolares (administración de catálogo, RF-03).
 */
class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_isbn' => strtoupper(str_replace(' ', '', trim((string) $this->codigo_isbn))),
            'activo' => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'codigo_isbn' => [
                'required', 'string', 'max:20', 'regex:/^[0-9A-Z-]+$/',
                Rule::unique('productos', 'codigo_isbn')->ignore($producto?->id),
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'serie' => ['required', Rule::in(Producto::SERIES)],
            'area' => ['required', 'string', 'max:100'],
            'nivel_educativo' => ['required', 'string', 'max:100'],
            'stock_actual' => ['required', 'integer', 'min:0', 'max:1000000'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_isbn.unique' => 'Ya existe un texto con este código SKU / ISBN.',
            'codigo_isbn.regex' => 'El código solo puede tener números, letras y guiones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo_isbn' => 'código SKU / ISBN',
            'titulo' => 'título',
            'area' => 'área / asignatura',
            'nivel_educativo' => 'grado / nivel',
            'stock_actual' => 'stock en bodega',
        ];
    }
}

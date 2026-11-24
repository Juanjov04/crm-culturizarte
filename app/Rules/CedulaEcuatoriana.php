<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida una cédula de identidad ecuatoriana (observación del jurado):
 * 10 dígitos, código de provincia 01-24 (o 30 para ecuatorianos en el exterior),
 * tercer dígito menor a 6 y dígito verificador según el algoritmo módulo 10
 * del Registro Civil.
 */
class CedulaEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::esValida((string) $value)) {
            $fail('La :attribute no es una cédula ecuatoriana válida.');
        }
    }

    public static function esValida(string $cedula): bool
    {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30)) {
            return false;
        }

        if ((int) $cedula[2] >= 6) {
            return false;
        }

        // Coeficientes 2,1,2,1... sobre los 9 primeros dígitos; si el producto pasa de 9 se resta 9.
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $producto = (int) $cedula[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $producto > 9 ? $producto - 9 : $producto;
        }

        $verificador = (10 - $suma % 10) % 10;

        return $verificador === (int) $cedula[9];
    }
}

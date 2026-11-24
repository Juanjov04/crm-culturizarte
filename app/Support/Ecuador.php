<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Catálogo de provincias y cantones de Ecuador (config/ecuador.php).
 */
class Ecuador
{
    /** @return array<string, list<string>> */
    public static function provincias(): array
    {
        return config('ecuador.provincias');
    }

    /** @return list<string> */
    public static function cantones(?string $provincia): array
    {
        return self::provincias()[$provincia] ?? [];
    }

    /**
     * Busca una ciudad escrita a mano (p. ej. "QUITO", "quito ") y devuelve
     * [provincia, cantón] con el nombre oficial, o null si no coincide.
     * Acepta también el nombre entre paréntesis ("Macas" → "Morona (Macas)").
     */
    public static function buscarCiudad(?string $ciudad): ?array
    {
        $buscado = self::normalizar($ciudad);
        if ($buscado === '') {
            return null;
        }

        foreach (self::provincias() as $provincia => $cantones) {
            foreach ($cantones as $canton) {
                $variantes = [self::normalizar($canton)];
                if (preg_match('/^(.*)\((.*)\)$/', $canton, $m)) {
                    $variantes[] = self::normalizar($m[1]);
                    $variantes[] = self::normalizar($m[2]);
                }

                if (in_array($buscado, $variantes, true)) {
                    return [$provincia, $canton];
                }
            }
        }

        return null;
    }

    private static function normalizar(?string $texto): string
    {
        return (string) Str::of((string) $texto)->ascii()->lower()->squish();
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Interpreta una fila del kardex de libros de la editorial
 * (CODIGO, NOMBRE, OBSERVACIONES, CANT) y la convierte en un texto del
 * catálogo del CRM: serie, área compatible, grado, edición y stock de bodega.
 */
class KardexLibros
{
    public const AREA_ECA = 'Educación Cultural y Artística (ECA)';

    public const AREA_LECTURITAS = 'Lengua y Literatura';

    /**
     * @return array{codigo_isbn: string, titulo: string, serie: string, area: string, nivel_educativo: string, edicion: ?string, stock_actual: int}|null
     */
    public static function interpretar(mixed $codigo, ?string $nombre, ?string $observaciones, mixed $cantidad): ?array
    {
        $codigo = strtoupper(trim((string) $codigo));
        $nombre = trim((string) $nombre);

        // Filas vacías, totales o notas al pie del kardex.
        if ($codigo === '' || $nombre === '' || ! is_numeric($cantidad)) {
            return null;
        }

        $clave = (string) Str::of($nombre)->ascii()->upper()->squish();
        $nivel = self::nivel($clave);

        if (str_contains($clave, 'LECTURITAS')) {
            [$serie, $area, $base] = ['Comprensión Lectora', self::AREA_LECTURITAS, 'Lecturitas'];
        } elseif (str_contains($clave, 'ARTISTICA')) {
            [$serie, $area, $base] = ['ECA', self::AREA_ECA, 'Educación Cultural y Artística'];
        } else {
            $base = self::tituloLegible($clave);
            [$serie, $area] = ['Otros', $base];
        }

        // Sin grado reconocible se conserva el nombre original (p. ej. "Educación Artística 1").
        $titulo = $nivel === null ? self::tituloLegible($clave) : "{$base} {$nivel}";

        return [
            'codigo_isbn' => $codigo,
            'titulo' => $titulo,
            'serie' => $serie,
            'area' => $area,
            'nivel_educativo' => $nivel ?? 'Sin especificar',
            'edicion' => self::edicion($observaciones),
            'stock_actual' => max(0, (int) $cantidad),
        ];
    }

    /** Grado o subnivel educativo según la nomenclatura del Mineduc. */
    public static function nivel(string $clave): ?string
    {
        if (str_contains($clave, 'PREPARATORIA')) {
            return '1.º EGB (Preparatoria)';
        }

        if (preg_match('/\b(\d{1,2})\s*(?:RO|DO|ER|TO|MO|VO|NO)?\s*BACH/', $clave, $m)) {
            return "{$m[1]}.º Bachillerato";
        }

        if (preg_match('/\b(\d{1,2})\s*(?:RO|DO|ER|TO|MO|VO|NO)\b/', $clave, $m)) {
            return "{$m[1]}.º EGB";
        }

        if (str_contains($clave, 'SUBNIVEL SUPERIOR')) {
            return 'EGB Superior';
        }

        if (str_contains($clave, 'BACHILLERATO')) {
            return 'Bachillerato';
        }

        return null;
    }

    private static function edicion(?string $observaciones): ?string
    {
        $texto = trim((string) preg_replace('/\s+/', ' ', (string) $observaciones));
        if ($texto === '') {
            return null;
        }

        $texto = Str::ucfirst(mb_strtolower($texto));

        return str_ireplace(
            ['mineduc', 'jose mosquera', 'edicion', 'numero', 'enfasis'],
            ['MINEDUC', 'José Mosquera', 'edición', 'número', 'énfasis'],
            $texto
        );
    }

    private static function tituloLegible(string $clave): string
    {
        $titulo = Str::title(mb_strtolower($clave));

        return str_replace(
            ['Educacion', 'Artistica', 'Quimica', ' Y '],
            ['Educación', 'Artística', 'Química', ' y '],
            $titulo
        );
    }
}

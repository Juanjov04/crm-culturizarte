<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Support\KardexLibros;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga el inventario real de la editorial (kardex de libros) en el catálogo
 * del CRM. El archivo es un CSV exportado desde Excel con las columnas
 * CODIGO, NOMBRE, OBSERVACIONES y CANT. Se guarda fuera de Git
 * (storage/app/private) porque las cantidades son información de la empresa.
 */
class ImportarCatalogo extends Command
{
    protected $signature = 'catalogo:importar
        {archivo? : CSV del kardex (por defecto storage/app/private/inventario/kardex.csv)}
        {--desactivar-otros : Marca como no disponibles los textos que no están en el archivo (p. ej. los de demostración)}
        {--simular : Muestra lo que se importaría sin guardar nada}';

    protected $description = 'Importa el kardex de libros de la editorial al catálogo de textos (stock de bodega)';

    public function handle(): int
    {
        $ruta = $this->argument('archivo') ?? storage_path('app/private/inventario/kardex.csv');

        if (! is_file($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");
            $this->line('Exporte el Excel del kardex como "CSV UTF-8 (delimitado por comas)" y vuelva a intentarlo.');

            return self::FAILURE;
        }

        $textos = $this->leer($ruta);
        if ($textos === null) {
            $this->error('El archivo no tiene la fila de encabezados CODIGO / NOMBRE / OBSERVACIONES / CANT.');

            return self::FAILURE;
        }

        $this->table(
            ['Código', 'Título', 'Edición', 'Serie', 'Área', 'Grado', 'Stock'],
            array_map(fn ($t) => [$t['codigo_isbn'], $t['titulo'], $t['edicion'] ?? '—', $t['serie'], $t['area'], $t['nivel_educativo'], $t['stock_actual']], $textos)
        );
        $this->info(count($textos).' textos, '.array_sum(array_column($textos, 'stock_actual')).' unidades en bodega.');

        if ($this->option('simular')) {
            $this->warn('Simulación: no se guardó ningún cambio.');

            return self::SUCCESS;
        }

        [$nuevos, $actualizados, $desactivados] = DB::transaction(function () use ($textos) {
            $nuevos = $actualizados = 0;

            foreach ($textos as $texto) {
                $producto = Producto::firstOrNew(['codigo_isbn' => $texto['codigo_isbn']]);
                $producto->exists ? $actualizados++ : $nuevos++;
                $producto->fill($texto + ['activo' => true])->save();
            }

            $desactivados = $this->option('desactivar-otros')
                ? Producto::whereNotIn('codigo_isbn', array_column($textos, 'codigo_isbn'))->where('activo', true)->update(['activo' => false])
                : 0;

            return [$nuevos, $actualizados, $desactivados];
        });

        $this->info("Catálogo actualizado: {$nuevos} nuevos, {$actualizados} actualizados, {$desactivados} marcados como no disponibles.");

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function leer(string $ruta): ?array
    {
        $contenido = (string) file_get_contents($ruta);
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (! mb_check_encoding($contenido, 'UTF-8')) {
            // Excel en español guarda "CSV" en Windows-1252 si no se elige UTF-8.
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $separador = substr_count($contenido, ';') > substr_count($contenido, ',') ? ';' : ',';

        $columnas = null;
        $textos = [];

        foreach ($lineas as $linea) {
            $celdas = str_getcsv($linea, $separador);

            if ($columnas === null) {
                $nombres = array_map(fn ($c) => (string) Str::of((string) $c)->ascii()->upper()->trim(), $celdas);
                if (in_array('CODIGO', $nombres, true) && in_array('NOMBRE', $nombres, true)) {
                    $columnas = [
                        'codigo' => array_search('CODIGO', $nombres, true),
                        'nombre' => array_search('NOMBRE', $nombres, true),
                        'observaciones' => array_search('OBSERVACIONES', $nombres, true),
                        'cantidad' => array_search('CANT', $nombres, true) ?: array_search('CANTIDAD', $nombres, true),
                    ];
                }

                continue;
            }

            $valor = fn (string $campo) => $columnas[$campo] === false ? null : ($celdas[$columnas[$campo]] ?? null);
            $texto = KardexLibros::interpretar($valor('codigo'), $valor('nombre'), $valor('observaciones'), $valor('cantidad'));

            if ($texto !== null) {
                $textos[] = $texto;
            }
        }

        return $columnas === null ? null : $textos;
    }
}

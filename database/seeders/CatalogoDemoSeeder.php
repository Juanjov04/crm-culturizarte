<?php

namespace Database\Seeders;

use App\Actions\AsignarStock;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Catálogo de textos de ejemplo (series ECA y Comprensión Lectora) con stock
 * referencial de bodega, y una asignación inicial al asesor demo.
 * Se reemplazará por el inventario real de la editorial (no usar en producción).
 */
class CatalogoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            ['codigo_isbn' => '978-9942-001-01', 'titulo' => 'Comprensión Lectora 2.º EGB', 'serie' => 'Comprensión Lectora', 'area' => 'Comprensión Lectora', 'nivel_educativo' => '2.º EGB', 'stock_actual' => 120],
            ['codigo_isbn' => '978-9942-001-05', 'titulo' => 'Comprensión Lectora 5.º EGB', 'serie' => 'Comprensión Lectora', 'area' => 'Comprensión Lectora', 'nivel_educativo' => '5.º EGB', 'stock_actual' => 90],
            ['codigo_isbn' => '978-9942-002-05', 'titulo' => 'Lengua y Literatura 5.º EGB', 'serie' => 'Comprensión Lectora', 'area' => 'Lengua y Literatura', 'nivel_educativo' => '5.º EGB', 'stock_actual' => 80],
            ['codigo_isbn' => '978-9942-002-08', 'titulo' => 'Lengua y Literatura 8.º EGB', 'serie' => 'Comprensión Lectora', 'area' => 'Lengua y Literatura', 'nivel_educativo' => '8.º EGB', 'stock_actual' => 60],
            ['codigo_isbn' => '978-9942-003-03', 'titulo' => 'ECA 3.º EGB', 'serie' => 'ECA', 'area' => 'Educación Cultural y Artística (ECA)', 'nivel_educativo' => '3.º EGB', 'stock_actual' => 70],
            ['codigo_isbn' => '978-9942-003-07', 'titulo' => 'ECA 7.º EGB', 'serie' => 'ECA', 'area' => 'Educación Cultural y Artística (ECA)', 'nivel_educativo' => '7.º EGB', 'stock_actual' => 0],
        ];

        foreach ($catalogo as $texto) {
            Producto::firstOrCreate(['codigo_isbn' => $texto['codigo_isbn']], $texto + ['activo' => true]);
        }

        $admin = User::where('email', 'admin@culturizarte.test')->first();
        $asesor = User::where('email', 'asesor@culturizarte.test')->first();

        if (! $admin || ! $asesor || InventarioAsesor::where('user_id', $asesor->id)->exists()) {
            return;
        }

        $asignar = app(AsignarStock::class);
        foreach (['978-9942-001-05' => 10, '978-9942-002-05' => 5, '978-9942-003-03' => 3] as $codigo => $cantidad) {
            $asignar($admin, $asesor, Producto::where('codigo_isbn', $codigo)->first(), $cantidad);
        }
    }
}

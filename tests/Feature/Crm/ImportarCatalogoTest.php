<?php

namespace Tests\Feature\Crm;

use App\Models\Producto;
use App\Support\KardexLibros;

/**
 * Carga del inventario real de la editorial (kardex de libros) al catálogo.
 * Usa datos ficticios: el kardex real de la empresa no se versiona.
 */
class ImportarCatalogoTest extends CrmTestCase
{
    private function csv(string $contenido): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'kardex').'.csv';
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    private function kardexDeEjemplo(): string
    {
        return $this->csv(implode("\n", [
            'INVENTARIO FISICO LIBROS,,,',
            ',,,',
            'CODIGO,NOMBRE,OBSERVACIONES,CANT',
            '43,LECTURITAS 2DO EGB,,100',
            '57,LECTURITAS 8VO,POR COMPETENCIAS,200',
            '67,EDUCACION CULTURAL Y ARTISTICA 2DO EGB,PROYECTOS POR COMPETENCIAS CON INSERCIONES CURRICULARES,300',
            '8A,EDUCACION CULTURAL Y ARTISTICA BACHILLERATO,SIN NUMERO 1,40',
            '33,QUIMICA,,10',
            ',,TOTAL,650',
        ]));
    }

    public function test_interpreta_las_filas_del_kardex(): void
    {
        $this->assertSame([
            'codigo_isbn' => '57',
            'titulo' => 'Lecturitas 8.º EGB',
            'serie' => 'Comprensión Lectora',
            'area' => 'Lengua y Literatura',
            'nivel_educativo' => '8.º EGB',
            'edicion' => 'Por competencias',
            'stock_actual' => 200,
        ], KardexLibros::interpretar(57, 'LECTURITAS 8VO', 'POR COMPETENCIAS', 200));

        $eca = KardexLibros::interpretar('51', 'EDUCACION CULTURAL Y ARTISTICA 1RO PREPARATORIA', 'PROYECTOS CON ENFASIS EN COMPETENCIAS', 25);
        $this->assertSame('Educación Cultural y Artística 1.º EGB (Preparatoria)', $eca['titulo']);
        $this->assertSame('ECA', $eca['serie']);
        $this->assertSame('Proyectos con énfasis en competencias', $eca['edicion']);

        $this->assertSame('2.º Bachillerato', KardexLibros::interpretar('13', 'EDUCACION CULTURAL Y ARTISTICA 2DO BACH', null, 15)['nivel_educativo']);
        $this->assertSame(['Otros', 'Química'], array_values(array_intersect_key(
            KardexLibros::interpretar('33', 'QUIMICA', null, 10), array_flip(['serie', 'area'])
        )));

        // Filas de total o notas al pie no son textos.
        $this->assertNull(KardexLibros::interpretar(null, null, 'TOTAL', 999));
        $this->assertNull(KardexLibros::interpretar(null, 'Excel Mily: Kardex libros', null, null));
    }

    public function test_importa_el_kardex_al_catalogo(): void
    {
        $this->artisan('catalogo:importar', ['archivo' => $this->kardexDeEjemplo()])
            ->expectsOutputToContain('5 textos, 650 unidades en bodega.')
            ->assertSuccessful();

        $this->assertSame(5, Producto::count());
        $this->assertDatabaseHas('productos', ['codigo_isbn' => '8A', 'titulo' => 'Educación Cultural y Artística Bachillerato', 'edicion' => 'Sin número 1', 'stock_actual' => 40]);
        $this->assertSame(650, (int) Producto::sum('stock_actual'));
    }

    public function test_reimportar_actualiza_el_stock_sin_duplicar_y_puede_desactivar_otros(): void
    {
        $demo = $this->producto(['codigo_isbn' => 'DEMO-1']);
        $this->producto(['codigo_isbn' => '43', 'stock_actual' => 5]);

        $this->artisan('catalogo:importar', ['archivo' => $this->kardexDeEjemplo(), '--desactivar-otros' => true])
            ->assertSuccessful();

        $this->assertSame(6, Producto::count());
        $this->assertSame(100, Producto::firstWhere('codigo_isbn', '43')->stock_actual);
        $this->assertFalse($demo->fresh()->activo);
    }

    public function test_simulacion_no_guarda_nada(): void
    {
        $this->artisan('catalogo:importar', ['archivo' => $this->kardexDeEjemplo(), '--simular' => true])
            ->assertSuccessful();

        $this->assertSame(0, Producto::count());
    }

    public function test_acepta_csv_de_excel_con_punto_y_coma_y_windows_1252(): void
    {
        $contenido = mb_convert_encoding("CÓDIGO;NOMBRE;OBSERVACIONES;CANT\n35;LECTURITAS 9NO;;55\n", 'Windows-1252', 'UTF-8');

        $this->artisan('catalogo:importar', ['archivo' => $this->csv($contenido)])->assertSuccessful();

        $this->assertDatabaseHas('productos', ['codigo_isbn' => '35', 'stock_actual' => 55]);
    }

    public function test_falla_si_no_existe_el_archivo_o_no_tiene_encabezados(): void
    {
        $this->artisan('catalogo:importar', ['archivo' => 'no-existe.csv'])->assertFailed();
        $this->artisan('catalogo:importar', ['archivo' => $this->csv("a,b,c\n1,2,3\n")])->assertFailed();
    }
}

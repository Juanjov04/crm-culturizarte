<?php

namespace Database\Seeders;

use App\Models\Docente;
use App\Models\Institucion;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo del directorio B2B para demos y pruebas (no usar en producción).
 */
class DirectorioDemoSeeder extends Seeder
{
    public function run(): void
    {
        $directorio = [
            [
                'institucion' => ['codigo_amie' => '17H00123', 'nombre' => 'Unidad Educativa San Francisco', 'tipo' => 'Particular', 'ciudad' => 'Quito', 'direccion' => 'Av. 10 de Agosto N24-15', 'telefono' => '022501234'],
                'docentes' => [
                    ['nombres' => 'María Fernanda', 'apellidos' => 'López Andrade', 'area_asignatura' => 'Lengua y Literatura', 'telefono' => '0991234567'],
                    ['nombres' => 'Carlos Andrés', 'apellidos' => 'Pérez Mora', 'area_asignatura' => 'Matemática', 'telefono' => '0987654321'],
                ],
            ],
            [
                'institucion' => ['codigo_amie' => '17H00456', 'nombre' => 'Colegio Fiscomisional La Salle', 'tipo' => 'Fiscomisional', 'ciudad' => 'Quito', 'direccion' => 'Calle Rocafuerte OE3-45', 'telefono' => '022955678'],
                'docentes' => [
                    ['nombres' => 'Ana Lucía', 'apellidos' => 'Vásconez Ruiz', 'area_asignatura' => 'Comprensión Lectora', 'telefono' => '0998877665'],
                    ['nombres' => 'Jorge Luis', 'apellidos' => 'Salazar Toapanta', 'area_asignatura' => 'Educación Cultural y Artística (ECA)', 'telefono' => null],
                ],
            ],
            [
                'institucion' => ['codigo_amie' => '09H00789', 'nombre' => 'Unidad Educativa Fiscal Guayaquil', 'tipo' => 'Fiscal', 'ciudad' => 'Guayaquil', 'direccion' => 'Av. 9 de Octubre 1200', 'telefono' => '042345678'],
                'docentes' => [
                    ['nombres' => 'Patricia', 'apellidos' => 'Cedeño Villamar', 'area_asignatura' => 'Lengua y Literatura', 'telefono' => '0976543210'],
                ],
            ],
        ];

        foreach ($directorio as $item) {
            $institucion = Institucion::firstOrCreate(
                ['codigo_amie' => $item['institucion']['codigo_amie']],
                $item['institucion'] + ['activo' => true]
            );

            foreach ($item['docentes'] as $docente) {
                Docente::firstOrCreate(
                    ['institucion_id' => $institucion->id, 'nombres' => $docente['nombres'], 'apellidos' => $docente['apellidos']],
                    $docente
                );
            }
        }
    }
}

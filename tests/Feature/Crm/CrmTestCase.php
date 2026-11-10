<?php

namespace Tests\Feature\Crm;

use App\Models\Docente;
use App\Models\Institucion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class CrmTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
    }

    protected function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    protected function institucion(array $datos = []): Institucion
    {
        static $n = 0;
        $n++;

        return Institucion::create($datos + [
            'codigo_amie' => '17H'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'nombre' => "Unidad Educativa {$n}",
            'tipo' => 'Particular',
            'ciudad' => 'Quito',
            'direccion' => 'Av. Siempre Viva 123',
            'activo' => true,
        ]);
    }

    protected function docente(Institucion $institucion, array $datos = []): Docente
    {
        return Docente::create($datos + [
            'institucion_id' => $institucion->id,
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'area_asignatura' => 'Lengua y Literatura',
        ]);
    }
}

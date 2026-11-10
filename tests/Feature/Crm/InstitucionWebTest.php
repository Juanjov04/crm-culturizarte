<?php

namespace Tests\Feature\Crm;

use App\Models\Institucion;

/**
 * Sprint 3 - CRUD de instituciones educativas.
 */
class InstitucionWebTest extends CrmTestCase
{
    public function test_administrador_ve_el_listado(): void
    {
        $this->institucion(['nombre' => 'Colegio Los Andes']);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('instituciones.index'))
            ->assertOk()
            ->assertSee('Colegio Los Andes');
    }

    public function test_asesor_no_puede_gestionar_instituciones(): void
    {
        $this->actingAs($this->usuarioConRol('Asesor'))
            ->get(route('instituciones.index'))
            ->assertForbidden();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('instituciones.index'))->assertRedirect(route('login'));
    }

    public function test_crea_una_institucion(): void
    {
        $respuesta = $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('instituciones.store'), [
                'codigo_amie' => '17h09999',
                'nombre' => 'Unidad Educativa Nueva',
                'tipo' => 'Fiscal',
                'ciudad' => 'Quito',
                'direccion' => 'Calle 1',
                'activo' => '1',
            ]);

        $institucion = Institucion::firstWhere('codigo_amie', '17H09999');
        $this->assertNotNull($institucion, 'El código AMIE debe guardarse en mayúsculas.');
        $respuesta->assertRedirect(route('instituciones.show', $institucion));
    }

    public function test_no_permite_codigo_amie_duplicado(): void
    {
        $this->institucion(['codigo_amie' => '17H00001']);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('instituciones.store'), [
                'codigo_amie' => '17H00001',
                'nombre' => 'Otra',
                'tipo' => 'Particular',
                'ciudad' => 'Quito',
                'direccion' => 'Calle 2',
            ])
            ->assertSessionHasErrors('codigo_amie');

        $this->assertSame(1, Institucion::where('codigo_amie', '17H00001')->count());
    }

    public function test_actualiza_y_alerta_al_inactivar_con_docentes(): void
    {
        $institucion = $this->institucion();
        $this->docente($institucion);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->put(route('instituciones.update', $institucion), [
                'codigo_amie' => $institucion->codigo_amie,
                'nombre' => 'Nombre actualizado',
                'tipo' => 'Particular',
                'ciudad' => 'Quito',
                'direccion' => 'Calle 3',
                'activo' => '0',
            ])
            ->assertRedirect(route('instituciones.show', $institucion))
            ->assertSessionHas('warning');

        $institucion->refresh();
        $this->assertFalse($institucion->activo);
        $this->assertSame('Nombre actualizado', $institucion->nombre);
    }

    public function test_no_elimina_institucion_con_visitas(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $asesor = $this->usuarioConRol('Asesor');
        $institucion->visitas()->create([
            'uuid_offline' => fake()->uuid(),
            'user_id' => $asesor->id,
            'docente_id' => $docente->id,
            'fecha_visita' => now()->subDay(),
        ]);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->delete(route('instituciones.destroy', $institucion))
            ->assertSessionHas('warning');

        $this->assertModelExists($institucion);
    }

    public function test_elimina_institucion_sin_visitas(): void
    {
        $institucion = $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->delete(route('instituciones.destroy', $institucion))
            ->assertRedirect(route('instituciones.index'));

        $this->assertModelMissing($institucion);
    }
}

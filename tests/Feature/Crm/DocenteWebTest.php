<?php

namespace Tests\Feature\Crm;

use App\Models\Docente;

/**
 * Sprint 3 - CRUD de docentes vinculados a instituciones.
 */
class DocenteWebTest extends CrmTestCase
{
    public function test_crea_docente_en_institucion_activa(): void
    {
        $institucion = $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('docentes.store'), [
                'institucion_id' => $institucion->id,
                'nombres' => 'Luis',
                'apellidos' => 'Torres',
                'cedula' => '1712345678',
                'area_asignatura' => 'Matemática',
            ])
            ->assertRedirect(route('instituciones.show', $institucion));

        $this->assertDatabaseHas('docentes', ['cedula' => '1712345678', 'institucion_id' => $institucion->id]);
    }

    public function test_no_permite_asignar_docente_a_institucion_inactiva(): void
    {
        $inactiva = $this->institucion(['activo' => false]);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('docentes.store'), [
                'institucion_id' => $inactiva->id,
                'nombres' => 'Luis',
                'apellidos' => 'Torres',
                'area_asignatura' => 'Matemática',
            ])
            ->assertSessionHasErrors('institucion_id');

        $this->assertSame(0, Docente::count());
    }

    public function test_listado_filtra_por_institucion(): void
    {
        $a = $this->institucion();
        $b = $this->institucion();
        $this->docente($a, ['nombres' => 'Visible']);
        $this->docente($b, ['nombres' => 'Oculto']);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('docentes.index', ['institucion_id' => $a->id]))
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Oculto');
    }

    public function test_actualiza_docente(): void
    {
        $docente = $this->docente($this->institucion());

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->put(route('docentes.update', $docente), [
                'institucion_id' => $docente->institucion_id,
                'nombres' => 'Ana María',
                'apellidos' => 'Pérez',
                'area_asignatura' => 'Comprensión Lectora',
            ])
            ->assertRedirect(route('docentes.index'));

        $this->assertSame('Comprensión Lectora', $docente->refresh()->area_asignatura);
    }
}

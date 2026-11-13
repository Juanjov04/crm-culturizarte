<?php

namespace Tests\Feature\Crm;

use App\Models\Visita;
use Illuminate\Support\Str;

/**
 * Sprint 4 - Formulario web de visitas y auditoría.
 */
class VisitaWebTest extends CrmTestCase
{
    public function test_asesor_registra_visita_desde_el_formulario(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $asesor = $this->usuarioConRol('Asesor');

        $this->actingAs($asesor)->get(route('visitas.create'))->assertOk()->assertSee($institucion->nombre);

        $this->actingAs($asesor)
            ->post(route('visitas.store'), [
                'institucion_id' => $institucion->id,
                'docente_id' => $docente->id,
                'fecha_visita' => now()->subMinutes(30)->format('Y-m-d\TH:i'),
                'nivel_interes' => 'medio',
                'estado_adopcion' => 'prospecto',
                'observaciones' => 'Pidió catálogo digital',
            ])
            ->assertRedirect();

        $visita = Visita::firstOrFail();
        $this->assertSame('web', $visita->origen);
        $this->assertSame($asesor->id, $visita->user_id);
    }

    public function test_administrador_audita_visitas_de_todos(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $asesor = $this->usuarioConRol('Asesor');
        $asesor->update(['name' => 'Asesor Uno']);

        $visita = Visita::create([
            'uuid_offline' => (string) Str::uuid(),
            'user_id' => $asesor->id,
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'fecha_visita' => now()->subDay(),
        ]);

        $admin = $this->usuarioConRol('Administrador');

        $this->actingAs($admin)->get(route('visitas.index'))->assertOk()->assertSee('Asesor Uno');
        $this->actingAs($admin)->get(route('visitas.show', $visita))->assertOk();
    }

    public function test_asesor_no_ve_visitas_ajenas(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $otro = $this->usuarioConRol('Asesor');

        $visita = Visita::create([
            'uuid_offline' => (string) Str::uuid(),
            'user_id' => $otro->id,
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'fecha_visita' => now()->subDay(),
        ]);

        $this->actingAs($this->usuarioConRol('Asesor'))
            ->get(route('visitas.show', $visita))
            ->assertForbidden();
    }

    public function test_dashboard_muestra_resumen(): void
    {
        $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Colegios activos');
    }
}

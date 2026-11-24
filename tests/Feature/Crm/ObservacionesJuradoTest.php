<?php

namespace Tests\Feature\Crm;

use App\Models\Visita;
use App\Rules\CedulaEcuatoriana;
use Laravel\Sanctum\Sanctum;

/**
 * Correcciones pedidas por el jurado: fecha de la visita y cédula del docente.
 */
class ObservacionesJuradoTest extends CrmTestCase
{
    private function visita(array $extra = []): array
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);

        return $extra + [
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'nivel_interes' => 'medio',
            'estado_adopcion' => 'prospecto',
        ];
    }

    public function test_rechaza_visita_con_fecha_anterior(): void
    {
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', $this->visita(['fecha_visita' => now()->subDay()->toIso8601String()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_visita');

        $this->postJson('/api/v1/visitas', $this->visita(['fecha_visita' => now()->subHour()->toIso8601String()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_visita');
    }

    public function test_acepta_la_fecha_actual_con_margen_de_llenado(): void
    {
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', $this->visita(['fecha_visita' => now()->subMinutes(5)->toIso8601String()]))
            ->assertCreated();
    }

    public function test_sin_fecha_se_usa_la_hora_actual(): void
    {
        $this->travelTo(now()->setTime(10, 30));
        $asesor = $this->usuarioConRol('Asesor');

        $this->actingAs($asesor)->post(route('visitas.store'), $this->visita())->assertRedirect();

        $this->assertSame(now()->format('Y-m-d H:i'), Visita::firstOrFail()->fecha_visita->format('Y-m-d H:i'));
    }

    public function test_el_formulario_web_no_permite_editar_la_fecha(): void
    {
        $this->actingAs($this->usuarioConRol('Asesor'))
            ->get(route('visitas.create'))
            ->assertOk()
            ->assertDontSee('name="fecha_visita"', false)
            ->assertSee('Se registra automáticamente al guardar.');
    }

    public function test_validador_de_cedula_ecuatoriana(): void
    {
        foreach (['1710034065', '0926687856', '1714616123', '3050000003'] as $valida) {
            $this->assertTrue(CedulaEcuatoriana::esValida($valida), "$valida debería ser válida");
        }

        foreach ([
            '1710034066', // dígito verificador incorrecto
            '1712345678', // dígito verificador incorrecto
            '2510034065', // provincia 25 no existe
            '0010034065', // provincia 00 no existe
            '1760034065', // tercer dígito >= 6 (no es persona natural)
            '171003406',  // menos de 10 dígitos
            '17100340a5', // contiene letras
        ] as $invalida) {
            $this->assertFalse(CedulaEcuatoriana::esValida($invalida), "$invalida debería ser inválida");
        }
    }

    public function test_formulario_de_docente_rechaza_cedula_invalida(): void
    {
        $institucion = $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('docentes.store'), [
                'institucion_id' => $institucion->id,
                'nombres' => 'Luis',
                'apellidos' => 'Mora',
                'cedula' => '1712345678',
                'area_asignatura' => 'Matemática',
            ])
            ->assertSessionHasErrors('cedula');

        $this->assertDatabaseMissing('docentes', ['cedula' => '1712345678']);
    }
}

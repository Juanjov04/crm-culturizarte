<?php

namespace Tests\Feature\Crm;

use App\Models\Institucion;
use App\Models\User;
use App\Models\Visita;
use App\Support\Indicadores;
use Illuminate\Support\Str;

/**
 * Sprint 7 - Panel de indicadores (RF-04, CU-W02) y rol Gerente.
 */
class IndicadoresTest extends CrmTestCase
{
    private function visita(Institucion $institucion, User $asesor, string $estado, string $fecha): Visita
    {
        $docente = $institucion->docentes()->first() ?? $this->docente($institucion);

        return Visita::create([
            'uuid_offline' => (string) Str::uuid(),
            'user_id' => $asesor->id,
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'fecha_visita' => $fecha,
            'nivel_interes' => 'medio',
            'estado_adopcion' => $estado,
        ]);
    }

    /**
     * Escenario: A adopta y luego deserta; B adopta; C solo interesado; D (Guayas) adopta.
     */
    private function escenario(): array
    {
        $asesor = $this->usuarioConRol('Asesor');
        $otro = $this->usuarioConRol('Asesor');
        $a = $this->institucion(['nombre' => 'Colegio A']);
        $b = $this->institucion(['nombre' => 'Colegio B']);
        $c = $this->institucion(['nombre' => 'Colegio C']);
        $d = $this->institucion(['nombre' => 'Colegio D', 'provincia' => 'Guayas', 'ciudad' => 'Guayaquil']);

        $this->visita($a, $asesor, 'adoptado', '2026-03-10 10:00');
        $this->visita($a, $asesor, 'desertado', '2026-09-15 10:00');
        $this->visita($b, $asesor, 'interesado', '2026-04-01 10:00');
        $this->visita($b, $asesor, 'adoptado', '2026-05-20 10:00');
        $this->visita($c, $otro, 'interesado', '2026-06-01 10:00');
        $this->visita($d, $otro, 'adoptado', '2026-07-01 10:00');

        return compact('asesor', 'otro', 'a', 'b', 'c', 'd');
    }

    public function test_calcula_tasa_de_adopcion_y_churn_rate(): void
    {
        $this->escenario();
        $kpis = (new Indicadores(['desde' => '2026-01-01', 'hasta' => '2026-12-31']))->kpis();

        $this->assertSame(6, $kpis['visitas']);
        $this->assertSame(4, $kpis['colegios_visitados']);
        // B y D terminan adoptados: 2 de 4.
        $this->assertSame(50.0, $kpis['tasa_adopcion']);
        // A, B y D adoptaron alguna vez; A desertó: 1 de 3.
        $this->assertSame(33.3, $kpis['churn_rate']);
    }

    public function test_el_churn_depende_de_la_fecha_final(): void
    {
        $this->escenario();

        // Antes de septiembre el colegio A todavía no había desertado.
        $this->assertSame(0.0, (new Indicadores(['desde' => '2026-01-01', 'hasta' => '2026-08-31']))->churnRate());
    }

    public function test_filtra_por_provincia_y_asesor(): void
    {
        ['otro' => $otro] = $this->escenario();

        $guayas = (new Indicadores(['desde' => '2026-01-01', 'hasta' => '2026-12-31', 'provincia' => 'Guayas']))->kpis();
        $this->assertSame(1, $guayas['visitas']);
        $this->assertSame(100.0, $guayas['tasa_adopcion']);

        $delOtro = (new Indicadores(['desde' => '2026-01-01', 'hasta' => '2026-12-31', 'asesor_id' => $otro->id]))->kpis();
        $this->assertSame(2, $delOtro['visitas']);
    }

    public function test_datos_de_los_graficos(): void
    {
        $this->escenario();
        $ind = new Indicadores(['desde' => '2026-01-01', 'hasta' => '2026-12-31']);

        $this->assertSame(['Prospecto' => 0, 'Interesado' => 1, 'Adoptado' => 2, 'Desertado' => 1], $ind->estadosDeColegios());
        $this->assertSame(['adoptados' => 1, 'desertados' => 0], $ind->adopcionesPorProvincia()['Guayas']);
        $this->assertSame(['adoptados' => 1, 'desertados' => 1], $ind->adopcionesPorProvincia()['Pichincha']);
        $this->assertCount(12, $ind->visitasPorMes());
        $this->assertSame(['adoptados' => 2, 'desertados' => 1], $ind->comparativaAnual()[2026]);
        $this->assertSame(6, $ind->rankingAsesores()->sum('visitas'));
        $this->assertSame(4, $ind->rankingAsesores()->sum('colegios'));
        $this->assertSame('desertado', $ind->estadoPorColegio()->firstWhere('colegio', 'Colegio A')->estado);
    }

    public function test_gerente_y_administrador_ven_el_panel(): void
    {
        $this->escenario();

        foreach (['Gerente', 'Administrador'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get(route('indicadores.index', ['desde' => '2026-01-01', 'hasta' => '2026-12-31']))
                ->assertOk()
                ->assertSee('Churn rate')
                ->assertSee('33.3 %')
                ->assertSee('Colegio A');
        }
    }

    public function test_asesor_no_accede_al_panel(): void
    {
        $asesor = $this->usuarioConRol('Asesor');

        $this->actingAs($asesor)->get(route('indicadores.index'))->assertForbidden();
        $this->actingAs($asesor)->get(route('indicadores.exportar', 'visitas'))->assertForbidden();
    }

    public function test_gerente_audita_visitas_pero_no_administra_ni_registra(): void
    {
        ['a' => $a, 'asesor' => $asesor] = $this->escenario();
        $gerente = $this->usuarioConRol('Gerente');
        $visita = Visita::where('user_id', $asesor->id)->first();

        $this->actingAs($gerente)->get(route('visitas.index'))->assertOk()->assertSee($asesor->name);
        $this->actingAs($gerente)->get(route('visitas.show', $visita))->assertOk();
        $this->actingAs($gerente)->get(route('visitas.create'))->assertForbidden();
        $this->actingAs($gerente)->get(route('instituciones.index'))->assertForbidden();
        $this->actingAs($gerente)->get(route('productos.index'))->assertForbidden();
    }

    public function test_exporta_csv_para_excel_y_power_bi(): void
    {
        $this->escenario();
        $gerente = $this->usuarioConRol('Gerente');
        $filtros = ['desde' => '2026-01-01', 'hasta' => '2026-12-31'];

        $visitas = $this->actingAs($gerente)->get(route('indicadores.exportar', ['tipo' => 'visitas'] + $filtros));
        $visitas->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $visitas->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lineas = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertSame(7, count($lineas), 'Encabezado + 6 visitas');
        $this->assertStringContainsString('id_visita;fecha;asesor;codigo_amie', $lineas[0]);

        $colegios = $this->actingAs($gerente)->get(route('indicadores.exportar', ['tipo' => 'colegios'] + $filtros))->streamedContent();
        $this->assertStringContainsString('Colegio A', $colegios);
        $this->assertStringContainsString('desertado', $colegios);

        $this->actingAs($gerente)->get(route('indicadores.exportar', ['tipo' => 'muestras'] + $filtros))->assertOk();
        $this->actingAs($gerente)->get('/indicadores/exportar/usuarios')->assertNotFound();
    }

    public function test_vista_para_pdf(): void
    {
        $this->escenario();

        $this->actingAs($this->usuarioConRol('Gerente'))
            ->get(route('indicadores.imprimir', ['desde' => '2026-01-01', 'hasta' => '2026-12-31', 'provincia' => 'Guayas']))
            ->assertOk()
            ->assertSee('Guardar como PDF')
            ->assertSee('Del 01/01/2026 al 31/12/2026')
            ->assertSee('Colegio D')
            ->assertDontSee('Colegio A');
    }

    public function test_valida_el_rango_de_fechas(): void
    {
        $this->actingAs($this->usuarioConRol('Gerente'))
            ->get(route('indicadores.index', ['desde' => '2026-05-01', 'hasta' => '2026-01-01']))
            ->assertSessionHasErrors('hasta');
    }
}

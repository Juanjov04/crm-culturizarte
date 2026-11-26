<?php

namespace Tests\Feature\Crm;

use App\Actions\AsignarStock;
use App\Models\InventarioAsesor;
use App\Models\Visita;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Sprint 6 - Registro offline y sincronización asíncrona (RF-01, RF-02).
 */
class SincronizacionTest extends CrmTestCase
{
    private function visitaOffline(int $institucionId, int $docenteId, array $extra = []): array
    {
        return $extra + [
            'uuid_offline' => (string) Str::uuid(),
            'institucion_id' => $institucionId,
            'docente_id' => $docenteId,
            'fecha_visita' => now()->subDays(2)->toIso8601String(),
            'nivel_interes' => 'alto',
            'estado_adopcion' => 'interesado',
            'acuerdos' => 'Revisará las muestras con su área',
            'observaciones' => 'Docente receptivo',
        ];
    }

    public function test_sincroniza_un_paquete_de_visitas_capturadas_sin_conexion(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $asesor = $this->usuarioConRol('Asesor');
        Sanctum::actingAs($asesor);

        $paquete = [
            $this->visitaOffline($institucion->id, $docente->id),
            $this->visitaOffline($institucion->id, $docente->id, ['fecha_visita' => now()->subHours(3)->toIso8601String()]),
        ];

        $this->postJson('/api/v1/sincronizacion', ['visitas' => $paquete])
            ->assertOk()
            ->assertJsonPath('data.sincronizadas', 2)
            ->assertJsonPath('data.rechazadas', 0)
            ->assertJsonPath('data.resultados.0.estado', 'sincronizado')
            ->assertJsonPath('data.resultados.0.uuid_offline', $paquete[0]['uuid_offline']);

        $visita = Visita::where('uuid_offline', $paquete[0]['uuid_offline'])->firstOrFail();
        $this->assertTrue($visita->capturada_offline);
        $this->assertNotNull($visita->sincronizada_en);
        $this->assertSame('movil', $visita->origen);
        // Conserva la hora en que se registró en el teléfono, no la de la sincronización.
        $this->assertSame(now()->subDays(2)->format('Y-m-d'), $visita->fecha_visita->format('Y-m-d'));
    }

    public function test_reenviar_el_paquete_no_duplica_visitas_ni_stock(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        $asesor = $this->usuarioConRol('Asesor');
        app(AsignarStock::class)($this->usuarioConRol('Administrador'), $asesor, $producto, 5);
        Sanctum::actingAs($asesor);

        $paquete = ['visitas' => [$this->visitaOffline($institucion->id, $docente->id, [
            'muestras' => [['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 2]],
        ])]];

        $this->postJson('/api/v1/sincronizacion', $paquete)->assertJsonPath('data.resultados.0.estado', 'sincronizado');
        // Se cortó la señal antes de recibir la respuesta y la app reintenta.
        $this->postJson('/api/v1/sincronizacion', $paquete)->assertJsonPath('data.resultados.0.estado', 'duplicado');

        $this->assertSame(1, Visita::count());
        $this->assertSame(3, (int) InventarioAsesor::where('user_id', $asesor->id)->value('cantidad'));
    }

    public function test_una_visita_rechazada_no_bloquea_a_las_demas(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $sinStock = $this->visitaOffline($institucion->id, $docente->id, [
            'muestras' => [['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 1]],
        ]);
        $valida = $this->visitaOffline($institucion->id, $docente->id);

        $respuesta = $this->postJson('/api/v1/sincronizacion', ['visitas' => [$sinStock, $valida]])
            ->assertOk()
            ->assertJsonPath('data.sincronizadas', 1)
            ->assertJsonPath('data.rechazadas', 1)
            ->assertJsonPath('data.resultados.0.estado', 'rechazado')
            ->assertJsonPath('data.resultados.1.estado', 'sincronizado');

        $this->assertStringContainsString('No tienes stock', $respuesta->json('data.resultados.0.errores.0'));
        $this->assertSame(1, Visita::count());
    }

    public function test_visita_con_senal_que_quedo_en_cola_se_acepta_y_no_se_marca_offline(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $datos = $this->visitaOffline($institucion->id, $docente->id, [
            'capturada_offline' => false,
            'fecha_visita' => now()->subHours(5)->toIso8601String(),
        ]);

        $this->postJson('/api/v1/sincronizacion', ['visitas' => [$datos]])->assertJsonPath('data.sincronizadas', 1);
        $this->assertFalse(Visita::firstOrFail()->capturada_offline);
    }

    public function test_rechaza_visitas_offline_demasiado_antiguas_o_lejanas(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/sincronizacion', ['visitas' => [
            $this->visitaOffline($institucion->id, $docente->id, ['fecha_visita' => now()->subDays(Visita::DIAS_OFFLINE + 1)->toIso8601String()]),
            $this->visitaOffline($institucion->id, $docente->id, ['fecha_visita' => now()->addDays(Visita::MAX_DIAS_FUTURO + 1)->toIso8601String()]),
        ]])
            ->assertOk()
            ->assertJsonPath('data.rechazadas', 2);

        $this->assertSame(0, Visita::count());
    }

    public function test_el_registro_en_linea_sigue_sin_aceptar_fechas_anteriores(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        // Sin la marca de captura offline, la regla del jurado sigue igual.
        $this->postJson('/api/v1/visitas', $this->visitaOffline($institucion->id, $docente->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_visita');
    }

    public function test_no_permite_tomar_el_uuid_de_la_visita_de_otro_asesor(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $datos = $this->visitaOffline($institucion->id, $docente->id);

        Sanctum::actingAs($this->usuarioConRol('Asesor'));
        $this->postJson('/api/v1/sincronizacion', ['visitas' => [$datos]])->assertJsonPath('data.sincronizadas', 1);

        Sanctum::actingAs($this->usuarioConRol('Asesor'));
        $this->postJson('/api/v1/sincronizacion', ['visitas' => [$datos]])
            ->assertJsonPath('data.resultados.0.estado', 'rechazado');
    }

    public function test_valida_el_paquete(): void
    {
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/sincronizacion', ['visitas' => []])->assertUnprocessable();
        $this->postJson('/api/v1/sincronizacion', ['visitas' => [['institucion_id' => 1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visitas.0.uuid_offline');
    }

    public function test_requiere_token(): void
    {
        $this->postJson('/api/v1/sincronizacion', ['visitas' => []])->assertUnauthorized();
    }
}

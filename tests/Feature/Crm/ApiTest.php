<?php

namespace Tests\Feature\Crm;

use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * API de la app móvil: login (Sprint 2/3), directorio (Sprint 3) y visitas (Sprint 4).
 */
class ApiTest extends CrmTestCase
{
    public function test_login_devuelve_token(): void
    {
        $asesor = User::factory()->create(['password' => 'secreto123'])->assignRole('Asesor');

        $this->postJson('/api/v1/auth/login', ['email' => $asesor->email, 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('status', 'Success')
            ->assertJsonPath('data.usuario.roles.0', 'Asesor')
            ->assertJsonStructure(['data' => ['token', 'expira_en']]);
    }

    public function test_login_rechaza_credenciales_incorrectas(): void
    {
        $asesor = $this->usuarioConRol('Asesor');

        $this->postJson('/api/v1/auth/login', ['email' => $asesor->email, 'password' => 'mala'])
            ->assertStatus(401)
            ->assertJsonPath('status', 'Error');
    }

    public function test_login_rechaza_usuario_sin_rol(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertForbidden();
    }

    public function test_endpoints_requieren_token(): void
    {
        $this->getJson('/api/v1/instituciones')->assertUnauthorized();
        $this->postJson('/api/v1/visitas', [])->assertUnauthorized();
    }

    public function test_lista_solo_instituciones_activas_con_docentes(): void
    {
        $activa = $this->institucion(['nombre' => 'Activa']);
        $this->docente($activa);
        $this->institucion(['nombre' => 'Inactiva', 'activo' => false]);

        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->getJson('/api/v1/instituciones?con_docentes=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Activa')
            ->assertJsonPath('data.0.docentes.0.nombre_completo', 'Ana Pérez');
    }

    public function test_registra_visita_desde_la_app(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $asesor = $this->usuarioConRol('Asesor');
        Sanctum::actingAs($asesor);

        $uuid = (string) Str::uuid();
        $datos = [
            'uuid_offline' => $uuid,
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'fecha_visita' => now()->subHour()->toIso8601String(),
            'nivel_interes' => 'alto',
            'estado_adopcion' => 'interesado',
            'acuerdos' => 'Enviar muestras de 5to EGB',
        ];

        $this->postJson('/api/v1/visitas', $datos)
            ->assertCreated()
            ->assertJsonPath('data.uuid_offline', $uuid)
            ->assertJsonPath('data.origen', 'movil');

        // Reenviar la misma visita (p. ej. reintento por mala señal) no la duplica.
        $this->postJson('/api/v1/visitas', $datos)->assertOk();

        $this->assertSame(1, Visita::count());
        $this->assertSame($asesor->id, Visita::first()->user_id);
    }

    public function test_rechaza_visita_con_fecha_futura(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', [
            'institucion_id' => $institucion->id,
            'docente_id' => $docente->id,
            'fecha_visita' => now()->addDay()->toIso8601String(),
            'nivel_interes' => 'medio',
            'estado_adopcion' => 'prospecto',
        ])->assertUnprocessable()->assertJsonValidationErrors('fecha_visita');
    }

    public function test_rechaza_docente_de_otra_institucion(): void
    {
        $a = $this->institucion();
        $b = $this->institucion();
        $docenteDeB = $this->docente($b);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', [
            'institucion_id' => $a->id,
            'docente_id' => $docenteDeB->id,
            'fecha_visita' => now()->subHour()->toIso8601String(),
            'nivel_interes' => 'medio',
            'estado_adopcion' => 'prospecto',
        ])->assertUnprocessable()->assertJsonValidationErrors('docente_id');
    }

    public function test_asesor_solo_ve_sus_visitas(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $yo = $this->usuarioConRol('Asesor');
        $otro = $this->usuarioConRol('Asesor');

        foreach ([$yo, $otro] as $asesor) {
            Visita::create([
                'uuid_offline' => (string) Str::uuid(),
                'user_id' => $asesor->id,
                'institucion_id' => $institucion->id,
                'docente_id' => $docente->id,
                'fecha_visita' => now()->subDay(),
            ]);
        }

        Sanctum::actingAs($yo);

        $this->getJson('/api/v1/visitas')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.user_id', $yo->id);

        $ajena = Visita::where('user_id', $otro->id)->first();
        $this->getJson("/api/v1/visitas/{$ajena->id}")->assertForbidden();
    }
}

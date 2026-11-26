<?php

namespace Tests\Feature\Crm;

use App\Models\Institucion;
use App\Models\Visita;
use App\Rules\CedulaEcuatoriana;
use App\Support\Ecuador;
use Laravel\Sanctum\Sanctum;

/**
 * Correcciones pedidas por el jurado: fecha de la visita, cédula del docente
 * y provincia/ciudad de la institución elegidas de una lista.
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
                'telefono' => '0991234567',
                'email' => 'luis.mora@colegio.ec',
                'area_asignatura' => 'Matemática',
            ])
            ->assertSessionHasErrors('cedula');

        $this->assertDatabaseMissing('docentes', ['cedula' => '1712345678']);
    }

    public function test_catalogo_tiene_las_24_provincias_y_223_cantones(): void
    {
        $provincias = Ecuador::provincias();

        $this->assertCount(24, $provincias);
        $this->assertSame(223, array_sum(array_map('count', $provincias)));
        $this->assertContains('Quito', Ecuador::cantones('Pichincha'));
    }

    public function test_corrige_ciudades_escritas_a_mano(): void
    {
        $this->assertSame(['Pichincha', 'Quito'], Ecuador::buscarCiudad('  QUITO '));
        $this->assertSame(['Morona Santiago', 'Morona (Macas)'], Ecuador::buscarCiudad('macas'));
        $this->assertNull(Ecuador::buscarCiudad('Quitoo'));
    }

    private function datosInstitucion(array $extra): array
    {
        return $extra + [
            'codigo_amie' => '17H07777',
            'nombre' => 'Unidad Educativa Prueba',
            'tipo' => 'Fiscal',
            'direccion' => 'Calle 1',
            'telefono' => '022501234',
            'email_contacto' => 'contacto@colegio.ec',
            'activo' => '1',
        ];
    }

    public function test_institucion_guarda_provincia_y_ciudad_de_la_lista(): void
    {
        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('instituciones.store'), $this->datosInstitucion(['provincia' => 'Azuay', 'ciudad' => 'Cuenca']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('institucions', ['codigo_amie' => '17H07777', 'provincia' => 'Azuay', 'ciudad' => 'Cuenca']);
    }

    public function test_rechaza_ciudad_mal_escrita_o_de_otra_provincia(): void
    {
        $admin = $this->usuarioConRol('Administrador');

        foreach ([
            ['provincia' => 'Pichincha', 'ciudad' => 'Quitoo'],
            ['provincia' => 'Pichincha', 'ciudad' => 'quito'],
            ['provincia' => 'Guayas', 'ciudad' => 'Quito'],
        ] as $datos) {
            $this->actingAs($admin)
                ->post(route('instituciones.store'), $this->datosInstitucion($datos))
                ->assertSessionHasErrors('ciudad');
        }

        $this->actingAs($admin)
            ->post(route('instituciones.store'), $this->datosInstitucion(['provincia' => 'Pichinchaa', 'ciudad' => 'Quito']))
            ->assertSessionHasErrors('provincia');

        $this->assertSame(0, Institucion::count());
    }

    public function test_formulario_de_institucion_usa_menus(): void
    {
        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('instituciones.create'))
            ->assertOk()
            ->assertSee('<select id="provincia"', false)
            ->assertSee('<select id="ciudad"', false)
            ->assertSee('Zamora Chinchipe');
    }

    public function test_docente_exige_todos_los_datos(): void
    {
        $institucion = $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('docentes.store'), ['institucion_id' => $institucion->id])
            ->assertSessionHasErrors(['nombres', 'apellidos', 'cedula', 'telefono', 'email', 'area_asignatura']);
    }

    public function test_cedula_y_telefono_no_aceptan_letras(): void
    {
        $institucion = $this->institucion();

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('docentes.store'), [
                'institucion_id' => $institucion->id,
                'nombres' => 'Luis',
                'apellidos' => 'Mora',
                'cedula' => '17100340a5',
                'telefono' => '099abc4567',
                'email' => 'luis.mora@colegio.ec',
                'area_asignatura' => 'Matemática',
            ])
            ->assertSessionHasErrors(['cedula', 'telefono']);
    }

    public function test_formulario_de_docente_solo_permite_numeros_en_la_cedula(): void
    {
        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('docentes.create'))
            ->assertOk()
            ->assertSee("this.value.replace(/[^0-9]/g, '')", false)
            ->assertDontSee('se valida el dígito verificador');
    }

    public function test_institucion_exige_telefono_y_correo_y_telefono_sin_letras(): void
    {
        $admin = $this->usuarioConRol('Administrador');
        $datos = $this->datosInstitucion(['provincia' => 'Azuay', 'ciudad' => 'Cuenca']);

        $this->actingAs($admin)
            ->post(route('instituciones.store'), array_diff_key($datos, array_flip(['telefono', 'email_contacto'])))
            ->assertSessionHasErrors(['telefono', 'email_contacto']);

        $this->actingAs($admin)
            ->post(route('instituciones.store'), ['telefono' => '07abc1234'] + $datos)
            ->assertSessionHasErrors('telefono');

        $this->assertSame(0, Institucion::count());
    }

    public function test_pantalla_de_acceso_solo_pide_correo_y_contrasena(): void
    {
        $asesor = $this->usuarioConRol('Asesor');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña')
            ->assertDontSee('Asesor Móvil')
            ->assertDontSee('Admin Maestro')
            ->assertDontSee('Seleccionar asesor de campo')
            ->assertDontSee('Contraseña del asesor')
            ->assertDontSee('Gestión de Visitas B2B e Inteligencia de Negocios')
            ->assertDontSee('El asesor registra sus visitas desde la app móvil')
            // No se exponen los correos de los usuarios registrados.
            ->assertDontSee($asesor->email);
    }
}

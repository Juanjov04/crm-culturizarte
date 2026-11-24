<?php

namespace Tests\Feature\Crm;

use App\Actions\AsignarStock;
use App\Models\AsignacionStock;
use App\Models\InventarioAsesor;
use App\Models\MuestraEntregada;
use App\Models\Producto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Sprint 5 - Control de stock y entrega de muestras (RF-03, CU-M03).
 */
class MuestrasTest extends CrmTestCase
{
    private function asignar(User $asesor, Producto $producto, int $cantidad): void
    {
        app(AsignarStock::class)($this->usuarioConRol('Administrador'), $asesor, $producto, $cantidad);
    }

    private function datosVisita(int $institucionId, int $docenteId, array $muestras): array
    {
        return [
            'uuid_offline' => (string) Str::uuid(),
            'institucion_id' => $institucionId,
            'docente_id' => $docenteId,
            'fecha_visita' => now()->toIso8601String(),
            'nivel_interes' => 'alto',
            'estado_adopcion' => 'interesado',
            'muestras' => $muestras,
        ];
    }

    private function stockDe(User $asesor, Producto $producto): int
    {
        return (int) InventarioAsesor::where('user_id', $asesor->id)->where('producto_id', $producto->id)->value('cantidad');
    }

    // --- Catálogo (panel web) ---

    public function test_administrador_agrega_texto_al_catalogo(): void
    {
        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('productos.store'), [
                'codigo_isbn' => '978-9942-100-01',
                'titulo' => 'ECA 4.º EGB',
                'serie' => 'ECA',
                'area' => 'Educación Cultural y Artística (ECA)',
                'nivel_educativo' => '4.º EGB',
                'stock_actual' => 40,
                'activo' => '1',
            ])
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', ['codigo_isbn' => '978-9942-100-01', 'stock_actual' => 40, 'activo' => true]);
    }

    public function test_codigo_del_texto_es_unico(): void
    {
        $this->producto(['codigo_isbn' => '978-1']);

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('productos.store'), [
                'codigo_isbn' => '978-1', 'titulo' => 'Otro', 'serie' => 'ECA', 'area' => 'ECA',
                'nivel_educativo' => '1.º EGB', 'stock_actual' => 1,
            ])
            ->assertSessionHasErrors('codigo_isbn');
    }

    public function test_asesor_no_administra_el_catalogo(): void
    {
        $asesor = $this->usuarioConRol('Asesor');

        $this->actingAs($asesor)->get(route('productos.index'))->assertForbidden();
        $this->actingAs($asesor)->post(route('inventario.store'), [])->assertForbidden();
    }

    // --- Asignación de stock de bodega a asesores ---

    public function test_administrador_asigna_muestras_a_un_asesor(): void
    {
        $producto = $this->producto(['stock_actual' => 20]);
        $asesor = $this->usuarioConRol('Asesor');
        $admin = $this->usuarioConRol('Administrador');

        $this->actingAs($admin)
            ->post(route('inventario.store'), ['asesor_id' => $asesor->id, 'producto_id' => $producto->id, 'cantidad' => 8])
            ->assertRedirect(route('inventario.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(12, $producto->fresh()->stock_actual);
        $this->assertSame(8, $this->stockDe($asesor, $producto));
        $this->assertDatabaseHas('asignaciones_stock', ['asesor_id' => $asesor->id, 'asignado_por' => $admin->id, 'cantidad' => 8]);

        // Una segunda asignación se suma al inventario existente.
        $this->actingAs($admin)
            ->post(route('inventario.store'), ['asesor_id' => $asesor->id, 'producto_id' => $producto->id, 'cantidad' => 2]);
        $this->assertSame(10, $this->stockDe($asesor, $producto));
    }

    public function test_no_se_asigna_mas_de_lo_que_hay_en_bodega(): void
    {
        $producto = $this->producto(['stock_actual' => 3]);
        $asesor = $this->usuarioConRol('Asesor');

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->post(route('inventario.store'), ['asesor_id' => $asesor->id, 'producto_id' => $producto->id, 'cantidad' => 4])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(3, $producto->fresh()->stock_actual);
        $this->assertSame(0, AsignacionStock::count());
    }

    public function test_solo_se_asigna_stock_a_asesores(): void
    {
        $producto = $this->producto();
        $admin = $this->usuarioConRol('Administrador');

        $this->actingAs($admin)
            ->post(route('inventario.store'), ['asesor_id' => $admin->id, 'producto_id' => $producto->id, 'cantidad' => 1])
            ->assertSessionHasErrors('asesor_id');
    }

    public function test_paginas_de_inventario(): void
    {
        $producto = $this->producto(['titulo' => 'Texto Asignado']);
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $producto, 5);

        $this->actingAs($asesor)->get(route('inventario.index'))->assertOk()->assertSee('Mi inventario')->assertSee('Texto Asignado');
        $this->actingAs($this->usuarioConRol('Administrador'))->get(route('inventario.index'))->assertOk()->assertSee($asesor->name);
    }

    // --- Entrega de muestras en la visita (API móvil) ---

    public function test_entrega_fisica_descuenta_el_stock_del_asesor(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $producto, 5);
        Sanctum::actingAs($asesor);

        $datos = $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 2],
        ]);

        $this->postJson('/api/v1/visitas', $datos)
            ->assertCreated()
            ->assertJsonPath('data.muestras_entregadas.0.cantidad', 2)
            ->assertJsonPath('data.muestras_entregadas.0.formato', 'fisica');

        $this->assertSame(3, $this->stockDe($asesor, $producto));

        // El reintento de la misma visita no vuelve a descontar stock.
        $this->postJson('/api/v1/visitas', $datos)->assertOk();
        $this->assertSame(3, $this->stockDe($asesor, $producto));
        $this->assertSame(1, MuestraEntregada::count());
    }

    public function test_no_permite_muestra_fisica_sin_stock(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 1],
        ]))->assertUnprocessable()->assertJsonValidationErrors('muestras.0.cantidad');

        // Toda la visita se revierte.
        $this->assertSame(0, Visita::count());
    }

    public function test_no_permite_entregar_mas_de_lo_disponible(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $producto, 2);
        Sanctum::actingAs($asesor);

        $this->postJson('/api/v1/visitas', $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 3],
        ]))->assertUnprocessable()->assertJsonValidationErrors('muestras.0.cantidad');

        $this->assertSame(2, $this->stockDe($asesor, $producto));
    }

    public function test_muestra_digital_no_requiere_ni_descuenta_stock(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto();
        $asesor = $this->usuarioConRol('Asesor');
        Sanctum::actingAs($asesor);

        $this->postJson('/api/v1/visitas', $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'digital', 'cantidad' => 1],
        ]))->assertCreated();

        $this->assertDatabaseHas('muestra_entregadas', ['producto_id' => $producto->id, 'formato' => 'digital']);
        $this->assertSame(0, $this->stockDe($asesor, $producto));
    }

    public function test_texto_debe_ser_compatible_con_el_area_del_docente(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion, ['area_asignatura' => 'Matemática']);
        $producto = $this->producto(['area' => 'Lengua y Literatura']);
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $producto, 5);
        Sanctum::actingAs($asesor);

        $this->postJson('/api/v1/visitas', $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 1],
        ]))->assertUnprocessable()->assertJsonValidationErrors('muestras.0.producto_id');

        $this->assertSame(5, $this->stockDe($asesor, $producto));
    }

    public function test_compatibilidad_ignora_mayusculas_y_tildes(): void
    {
        $producto = $this->producto(['area' => 'Educación Cultural y Artística']);
        $docente = $this->docente($this->institucion(), ['area_asignatura' => '  educacion cultural  y artistica ']);

        $this->assertTrue($producto->esCompatibleCon($docente));
    }

    public function test_no_acepta_textos_inactivos(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto(['activo' => false]);
        Sanctum::actingAs($this->usuarioConRol('Asesor'));

        $this->postJson('/api/v1/visitas', $this->datosVisita($institucion->id, $docente->id, [
            ['producto_id' => $producto->id, 'formato' => 'digital', 'cantidad' => 1],
        ]))->assertUnprocessable()->assertJsonValidationErrors('muestras.0.producto_id');
    }

    public function test_app_consulta_el_inventario_del_asesor(): void
    {
        $conStock = $this->producto(['titulo' => 'Con stock', 'stock_actual' => 10]);
        $this->producto(['titulo' => 'Sin stock']);
        $this->producto(['titulo' => 'Inactivo', 'activo' => false]);
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $conStock, 4);
        Sanctum::actingAs($asesor);

        $respuesta = $this->getJson('/api/v1/inventario')->assertOk()->assertJsonCount(2, 'data');

        $item = collect($respuesta->json('data'))->firstWhere('titulo', 'Con stock');
        $this->assertSame(4, $item['stock_asesor']);
        $this->assertSame(6, $item['stock_bodega']);
    }

    // --- Panel web: formulario de visita e historial del docente ---

    public function test_formulario_web_registra_muestras_y_aparecen_en_el_historial_del_docente(): void
    {
        $institucion = $this->institucion();
        $docente = $this->docente($institucion);
        $producto = $this->producto(['titulo' => 'Lengua 5.º EGB']);
        $asesor = $this->usuarioConRol('Asesor');
        $this->asignar($asesor, $producto, 3);

        $this->actingAs($asesor)->get(route('visitas.create'))->assertOk()->assertSee('Asignación de muestras');

        $this->actingAs($asesor)
            ->post(route('visitas.store'), [
                'institucion_id' => $institucion->id,
                'docente_id' => $docente->id,
                'nivel_interes' => 'alto',
                'estado_adopcion' => 'interesado',
                'muestras' => [['producto_id' => $producto->id, 'formato' => 'fisica', 'cantidad' => 1]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->stockDe($asesor, $producto));

        $visita = Visita::firstOrFail();
        $this->actingAs($asesor)->get(route('visitas.show', $visita))->assertSee('Lengua 5.º EGB');

        $this->actingAs($this->usuarioConRol('Administrador'))
            ->get(route('docentes.show', $docente))
            ->assertOk()
            ->assertSee('Historial de muestras entregadas')
            ->assertSee('Lengua 5.º EGB');
    }
}

<?php

namespace App\Actions;

use App\Models\Docente;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registra una visita comercial (Sprint 4, RF-01) con las muestras
 * entregadas al docente (Sprint 5, RF-03).
 *
 * Si el dispositivo envía un uuid_offline que ya existe para el mismo asesor,
 * devuelve la visita existente en lugar de duplicarla (y no vuelve a
 * descontar stock). Así un reintento de la sincronización offline (Sprint 6) es seguro.
 */
class RegistrarVisita
{
    /**
     * @return array{0: Visita, 1: bool} La visita y si fue creada ahora.
     */
    public function __invoke(User $asesor, array $datos, string $origen): array
    {
        $uuid = $datos['uuid_offline'] ?? (string) Str::uuid();

        $existente = Visita::where('uuid_offline', $uuid)->first();
        if ($existente) {
            abort_unless($existente->user_id === $asesor->id, 409, 'El identificador de la visita ya está en uso.');

            return [$existente, false];
        }

        $visita = DB::transaction(function () use ($asesor, $datos, $origen, $uuid) {
            $visita = Visita::create([
                'uuid_offline' => $uuid,
                'user_id' => $asesor->id,
                'institucion_id' => $datos['institucion_id'],
                'docente_id' => $datos['docente_id'],
                // La visita queda con la fecha y hora del registro (observación del jurado).
                // Se guarda en la zona horaria de la aplicación aunque el móvil envíe otra.
                'fecha_visita' => isset($datos['fecha_visita'])
                    ? Carbon::parse($datos['fecha_visita'])->setTimezone(config('app.timezone'))
                    : now(),
                'nivel_interes' => $datos['nivel_interes'],
                'estado_adopcion' => $datos['estado_adopcion'],
                'acuerdos' => $datos['acuerdos'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'latitud' => $datos['latitud'] ?? null,
                'longitud' => $datos['longitud'] ?? null,
                'origen' => $origen,
                'estado_sincronizacion' => 'sincronizado',
                // Sprint 6: visita capturada sin conexión y enviada después por la app.
                'capturada_offline' => filter_var($datos['capturada_offline'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'sincronizada_en' => now(),
            ]);

            $this->registrarMuestras($asesor, $visita, $datos['muestras'] ?? []);

            return $visita;
        });

        return [$visita, true];
    }

    /**
     * RF-03: valida la compatibilidad con el área del docente y el stock del
     * asesor (todas las muestras son físicas), registra la entrega y descuenta el stock.
     */
    private function registrarMuestras(User $asesor, Visita $visita, array $muestras): void
    {
        if ($muestras === []) {
            return;
        }

        $docente = Docente::findOrFail($visita->docente_id);
        $productos = Producto::whereIn('id', array_column($muestras, 'producto_id'))->get()->keyBy('id');
        $errores = [];

        foreach (array_values($muestras) as $i => $muestra) {
            $producto = $productos[$muestra['producto_id']];
            $cantidad = (int) $muestra['cantidad'];

            if (! $producto->esCompatibleCon($docente)) {
                $errores["muestras.{$i}.producto_id"] = "«{$producto->nombre_completo}» es del área {$producto->area} y no corresponde al área del docente ({$docente->area_asignatura}).";

                continue;
            }

            $inventario = InventarioAsesor::where('user_id', $asesor->id)
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->first();
            $disponible = $inventario?->cantidad ?? 0;

            if ($disponible === 0) {
                $errores["muestras.{$i}.cantidad"] = "No tienes stock de «{$producto->nombre_completo}» para entregarlo como muestra.";

                continue;
            }

            if ($cantidad > $disponible) {
                $errores["muestras.{$i}.cantidad"] = "Solo tienes {$disponible} unidades de «{$producto->nombre_completo}» en tu inventario.";

                continue;
            }

            $inventario->decrement('cantidad', $cantidad);

            $visita->muestrasEntregadas()->create([
                'producto_id' => $producto->id,
                'formato' => 'fisica',
                'cantidad' => $cantidad,
            ]);
        }

        // Cualquier error revierte toda la transacción (visita, entregas y stock).
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}

<?php

namespace App\Actions;

use App\Models\AsignacionStock;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asigna muestras del stock referencial de bodega al inventario personal
 * de un asesor (Sprint 5, RF-03). No se puede asignar más de lo disponible.
 */
class AsignarStock
{
    public function __invoke(User $administrador, User $asesor, Producto $producto, int $cantidad): InventarioAsesor
    {
        return DB::transaction(function () use ($administrador, $asesor, $producto, $cantidad) {
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

            if ($cantidad > $producto->stock_actual) {
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente en bodega: solo hay {$producto->stock_actual} unidades de «{$producto->nombre_completo}».",
                ]);
            }

            $producto->decrement('stock_actual', $cantidad);

            $inventario = InventarioAsesor::firstOrCreate(
                ['user_id' => $asesor->id, 'producto_id' => $producto->id],
                ['cantidad' => 0]
            );
            $inventario->increment('cantidad', $cantidad);

            AsignacionStock::create([
                'producto_id' => $producto->id,
                'asesor_id' => $asesor->id,
                'asignado_por' => $administrador->id,
                'cantidad' => $cantidad,
            ]);

            return $inventario->refresh();
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sprint 5 - Consultar inventario disponible desde la app móvil (CU-M03, RF-03).
 */
class InventarioController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/inventario?buscar=...
     * Catálogo activo con el stock personal del asesor y el stock referencial de bodega.
     */
    public function index(Request $request): JsonResponse
    {
        $buscar = trim((string) $request->query('buscar'));
        $stock = InventarioAsesor::where('user_id', $request->user()->id)->pluck('cantidad', 'producto_id');

        $productos = Producto::activos()
            ->when($buscar !== '', fn ($q) => $q->where(function ($w) use ($buscar) {
                $w->where('titulo', 'like', "%{$buscar}%")
                    ->orWhere('codigo_isbn', 'like', "%{$buscar}%")
                    ->orWhere('area', 'like', "%{$buscar}%");
            }))
            ->orderBy('serie')
            ->orderBy('titulo')
            ->get()
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'codigo_isbn' => $p->codigo_isbn,
                'titulo' => $p->titulo,
                'serie' => $p->serie,
                'area' => $p->area,
                'nivel_educativo' => $p->nivel_educativo,
                'stock_asesor' => (int) ($stock[$p->id] ?? 0),
                'stock_bodega' => $p->stock_actual,
            ]);

        return $this->successResponse($productos);
    }
}

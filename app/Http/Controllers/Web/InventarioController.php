<?php

namespace App\Http\Controllers\Web;

use App\Actions\AsignarStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\AsignacionStockRequest;
use App\Models\AsignacionStock;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 5 - Control de stock de muestras (RF-03, CU-M03).
 * El administrador asigna muestras de bodega a los asesores y consulta su
 * inventario; el asesor consulta su inventario personal.
 */
class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user->esAdministrador()) {
            $inventario = InventarioAsesor::with('producto')
                ->where('user_id', $user->id)
                ->get()
                ->sortBy(fn ($i) => $i->producto->titulo)
                ->values();

            return view('inventario.asesor', compact('inventario'));
        }

        $asesorId = $request->query('asesor_id');

        $asesores = User::role('Asesor')->orderBy('name')->get(['id', 'name']);
        $productos = Producto::activos()->orderBy('serie')->orderBy('titulo')->get();

        $inventario = InventarioAsesor::with(['producto', 'user:id,name'])
            ->when($asesorId, fn ($q) => $q->where('user_id', $asesorId))
            ->where('cantidad', '>', 0)
            ->get()
            ->sortBy(fn ($i) => $i->user->name.$i->producto->titulo)
            ->values();

        $asignaciones = AsignacionStock::with(['producto:id,titulo', 'asesor:id,name', 'asignadoPor:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return view('inventario.index', compact('asesores', 'productos', 'inventario', 'asignaciones', 'asesorId'));
    }

    public function store(AsignacionStockRequest $request, AsignarStock $asignar): RedirectResponse
    {
        $datos = $request->validated();
        $asesor = User::findOrFail($datos['asesor_id']);
        $producto = Producto::findOrFail($datos['producto_id']);

        $asignar($request->user(), $asesor, $producto, (int) $datos['cantidad']);

        return redirect()
            ->route('inventario.index')
            ->with('status', "Se asignaron {$datos['cantidad']} unidades de «{$producto->titulo}» a {$asesor->name}.");
    }
}

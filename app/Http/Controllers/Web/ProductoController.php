<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Models\Docente;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 5 - Administración del catálogo de textos escolares y su stock
 * referencial de bodega (RF-03). Solo administrador.
 */
class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $serie = $request->query('serie');

        $productos = Producto::query()
            ->withSum('inventarios as stock_asesores', 'cantidad')
            ->when($buscar !== '', fn ($q) => $q->where(function ($w) use ($buscar) {
                $w->where('titulo', 'like', "%{$buscar}%")
                    ->orWhere('codigo_isbn', 'like', "%{$buscar}%")
                    ->orWhere('area', 'like', "%{$buscar}%");
            }))
            ->when(in_array($serie, Producto::SERIES, true), fn ($q) => $q->where('serie', $serie))
            ->orderBy('serie')
            ->orderBy('titulo')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', compact('productos', 'buscar', 'serie'));
    }

    public function create(): View
    {
        return view('productos.create', [
            'producto' => new Producto(['activo' => true, 'stock_actual' => 0]),
            'areas' => $this->areasConocidas(),
        ]);
    }

    public function store(ProductoRequest $request): RedirectResponse
    {
        $producto = Producto::create($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', "Texto «{$producto->titulo}» agregado al catálogo.");
    }

    public function edit(Producto $producto): View
    {
        return view('productos.edit', [
            'producto' => $producto,
            'areas' => $this->areasConocidas(),
        ]);
    }

    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', "Texto «{$producto->titulo}» actualizado.");
    }

    /**
     * Áreas ya usadas en docentes y catálogo, para sugerirlas y que la
     * validación de compatibilidad (RF-03) encuentre coincidencias.
     */
    private function areasConocidas()
    {
        return Docente::query()->distinct()->pluck('area_asignatura')
            ->merge(Producto::query()->distinct()->pluck('area'))
            ->filter()
            ->unique(fn ($a) => Producto::normalizarArea($a))
            ->sort()
            ->values();
    }
}

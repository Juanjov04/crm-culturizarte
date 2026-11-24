<?php

namespace App\Http\Controllers\Web;

use App\Actions\RegistrarVisita;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitaRequest;
use App\Models\Institucion;
use App\Models\InventarioAsesor;
use App\Models\Producto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 4 - Formulario de visitas comerciales y auditoría de la gestión de asesores (CU-W03).
 * El administrador ve todas las visitas; el asesor solo las suyas.
 */
class VisitaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filtros = $request->only(['asesor_id', 'institucion_id', 'desde', 'hasta', 'nivel_interes']);

        $visitas = Visita::query()
            ->with(['user:id,name', 'institucion:id,nombre', 'docente:id,nombres,apellidos'])
            ->when(! $user->esAdministrador(), fn ($q) => $q->where('user_id', $user->id))
            ->when($user->esAdministrador() && ! empty($filtros['asesor_id']), fn ($q) => $q->where('user_id', $filtros['asesor_id']))
            ->when(! empty($filtros['institucion_id']), fn ($q) => $q->where('institucion_id', $filtros['institucion_id']))
            ->when(! empty($filtros['nivel_interes']), fn ($q) => $q->where('nivel_interes', $filtros['nivel_interes']))
            ->when(! empty($filtros['desde']), fn ($q) => $q->whereDate('fecha_visita', '>=', $filtros['desde']))
            ->when(! empty($filtros['hasta']), fn ($q) => $q->whereDate('fecha_visita', '<=', $filtros['hasta']))
            ->latest('fecha_visita')
            ->paginate(15)
            ->withQueryString();

        $asesores = $user->esAdministrador()
            ? User::role(['Asesor', 'Administrador'])->orderBy('name')->get(['id', 'name'])
            : collect();

        $instituciones = Institucion::orderBy('nombre')->get(['id', 'nombre']);

        return view('visitas.index', compact('visitas', 'asesores', 'instituciones', 'filtros'));
    }

    public function create(Request $request): View
    {
        $instituciones = Institucion::activas()
            ->with(['docentes' => fn ($q) => $q->orderBy('apellidos')->select('id', 'institucion_id', 'nombres', 'apellidos', 'area_asignatura')])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_amie']);

        // Sprint 5 (RF-03): catálogo con el stock personal del asesor para el paso de muestras.
        $stock = InventarioAsesor::where('user_id', $request->user()->id)->pluck('cantidad', 'producto_id');
        $productos = Producto::activos()->orderBy('titulo')->get(['id', 'codigo_isbn', 'titulo', 'serie', 'area', 'nivel_educativo'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'titulo' => $p->titulo,
                'detalle' => "{$p->serie} · {$p->nivel_educativo} · {$p->codigo_isbn}",
                'area' => Producto::normalizarArea($p->area),
                'stock' => (int) ($stock[$p->id] ?? 0),
            ]);

        return view('visitas.create', compact('instituciones', 'productos'));
    }

    public function store(VisitaRequest $request, RegistrarVisita $registrar): RedirectResponse
    {
        [$visita] = $registrar($request->user(), $request->validated(), 'web');

        return redirect()
            ->route('visitas.show', $visita)
            ->with('status', 'Visita registrada correctamente.');
    }

    public function show(Request $request, Visita $visita): View
    {
        abort_unless(
            $visita->user_id === $request->user()->id || $request->user()->esAdministrador(),
            403
        );

        $visita->load(['user', 'institucion', 'docente', 'muestrasEntregadas.producto']);

        return view('visitas.show', compact('visita'));
    }
}

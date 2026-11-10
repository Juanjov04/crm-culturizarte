<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocenteRequest;
use App\Models\Docente;
use App\Models\Institucion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 3 - CRUD de docentes vinculados a instituciones (CU-W01, RF-05).
 */
class DocenteController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $institucionId = $request->query('institucion_id');

        $docentes = Docente::query()
            ->with('institucion')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('cedula', 'like', "%{$buscar}%")
                        ->orWhere('area_asignatura', 'like', "%{$buscar}%");
                });
            })
            ->when($institucionId, fn ($q) => $q->where('institucion_id', $institucionId))
            ->orderBy('apellidos')
            ->paginate(10)
            ->withQueryString();

        $instituciones = Institucion::orderBy('nombre')->get(['id', 'nombre']);

        return view('docentes.index', compact('docentes', 'instituciones', 'buscar', 'institucionId'));
    }

    public function create(Request $request): View
    {
        $docente = new Docente(['institucion_id' => $request->query('institucion_id')]);

        return view('docentes.create', [
            'docente' => $docente,
            'instituciones' => $this->institucionesActivas(),
        ]);
    }

    public function store(DocenteRequest $request): RedirectResponse
    {
        $docente = Docente::create($request->validated());

        return redirect()
            ->route('instituciones.show', $docente->institucion_id)
            ->with('status', "Docente {$docente->nombre_completo} registrado correctamente.");
    }

    public function edit(Docente $docente): View
    {
        return view('docentes.edit', [
            'docente' => $docente,
            'instituciones' => $this->institucionesActivas($docente->institucion_id),
        ]);
    }

    public function update(DocenteRequest $request, Docente $docente): RedirectResponse
    {
        $docente->update($request->validated());

        return redirect()
            ->route('docentes.index')
            ->with('status', "Docente {$docente->nombre_completo} actualizado correctamente.");
    }

    public function destroy(Docente $docente): RedirectResponse
    {
        if ($docente->visitas()->exists()) {
            return back()->with('warning', 'No se puede eliminar: el docente tiene visitas registradas.');
        }

        $docente->delete();

        return back()->with('status', 'Docente eliminado.');
    }

    /**
     * Instituciones activas para el selector (incluye la actual aunque esté inactiva, para mostrarla).
     */
    private function institucionesActivas(?int $incluirId = null)
    {
        return Institucion::query()
            ->where('activo', true)
            ->when($incluirId, fn ($q) => $q->orWhere('id', $incluirId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_amie', 'activo']);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitucionRequest;
use App\Models\Institucion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 3 - CRUD del directorio B2B: instituciones educativas (CU-W01, RF-05).
 */
class InstitucionController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = $request->query('estado');

        $instituciones = Institucion::query()
            ->withCount('docentes')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('codigo_amie', 'like', "%{$buscar}%")
                        ->orWhere('ciudad', 'like', "%{$buscar}%");
                });
            })
            ->when($estado === 'activas', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivas', fn ($q) => $q->where('activo', false))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('instituciones.index', compact('instituciones', 'buscar', 'estado'));
    }

    public function create(): View
    {
        return view('instituciones.create', ['institucion' => new Institucion(['activo' => true])]);
    }

    public function store(InstitucionRequest $request): RedirectResponse
    {
        $institucion = Institucion::create($request->validated());

        return redirect()
            ->route('instituciones.show', $institucion)
            ->with('status', 'Institución registrada correctamente.');
    }

    public function show(Institucion $institucion): View
    {
        $institucion->load(['docentes' => fn ($q) => $q->orderBy('apellidos')])
            ->loadCount('visitas');

        return view('instituciones.show', compact('institucion'));
    }

    public function edit(Institucion $institucion): View
    {
        return view('instituciones.edit', compact('institucion'));
    }

    public function update(InstitucionRequest $request, Institucion $institucion): RedirectResponse
    {
        $estabaActiva = $institucion->activo;
        $institucion->update($request->validated());

        $redirect = redirect()
            ->route('instituciones.show', $institucion)
            ->with('status', 'Institución actualizada correctamente.');

        // RF-05: si el colegio pasa a inactivo se alerta sobre el estado de sus docentes.
        $totalDocentes = $institucion->docentes()->count();
        if ($estabaActiva && ! $institucion->activo && $totalDocentes > 0) {
            $redirect->with('warning', "La institución quedó inactiva y tiene {$totalDocentes} docente(s) vinculado(s). Reasígnelos a otra institución activa para poder registrarles visitas.");
        }

        return $redirect;
    }

    public function destroy(Institucion $institucion): RedirectResponse
    {
        if ($institucion->visitas()->exists()) {
            return back()->with('warning', 'No se puede eliminar: la institución tiene visitas registradas. Márquela como inactiva en su lugar.');
        }

        $institucion->delete();

        return redirect()
            ->route('instituciones.index')
            ->with('status', 'Institución eliminada.');
    }
}

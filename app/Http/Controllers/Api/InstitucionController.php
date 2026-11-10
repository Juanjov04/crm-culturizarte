<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Institucion;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sprint 3 - Directorio B2B expuesto a la app móvil (descarga del catálogo, CU-M01).
 */
class InstitucionController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/instituciones?buscar=...&con_docentes=1
     */
    public function index(Request $request): JsonResponse
    {
        $buscar = trim((string) $request->query('buscar'));

        $instituciones = Institucion::activas()
            ->withCount('docentes')
            ->when($request->boolean('con_docentes'), fn ($q) => $q->with(['docentes' => fn ($d) => $d->orderBy('apellidos')]))
            ->when($buscar !== '', fn ($q) => $q->where(function ($w) use ($buscar) {
                $w->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo_amie', 'like', "%{$buscar}%")
                    ->orWhere('ciudad', 'like', "%{$buscar}%");
            }))
            ->orderBy('nombre')
            ->get();

        return $this->successResponse($instituciones);
    }

    public function show(Institucion $institucion): JsonResponse
    {
        $institucion->load(['docentes' => fn ($q) => $q->orderBy('apellidos')]);

        return $this->successResponse($institucion);
    }

    /**
     * GET /api/v1/instituciones/{institucion}/docentes
     */
    public function docentes(Institucion $institucion): JsonResponse
    {
        $docentes = Docente::where('institucion_id', $institucion->id)
            ->orderBy('apellidos')
            ->get();

        return $this->successResponse($docentes);
    }
}

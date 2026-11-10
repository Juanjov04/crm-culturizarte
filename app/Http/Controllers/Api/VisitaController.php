<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarVisita;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitaRequest;
use App\Models\Visita;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sprint 4 - Registro de interacciones (visitas) desde la app móvil (RF-01, CU-M02).
 */
class VisitaController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/visitas - visitas del asesor autenticado (más recientes primero).
     */
    public function index(Request $request): JsonResponse
    {
        $visitas = Visita::with(['institucion:id,nombre,codigo_amie', 'docente:id,nombres,apellidos,area_asignatura'])
            ->where('user_id', $request->user()->id)
            ->latest('fecha_visita')
            ->paginate(min((int) $request->query('por_pagina', 20), 100));

        return $this->successResponse($visitas);
    }

    /**
     * POST /api/v1/visitas
     */
    public function store(VisitaRequest $request, RegistrarVisita $registrar): JsonResponse
    {
        [$visita, $creada] = $registrar($request->user(), $request->validated(), 'movil');

        $visita->load(['institucion:id,nombre,codigo_amie', 'docente:id,nombres,apellidos,area_asignatura']);

        return $creada
            ? $this->successResponse($visita, 'Visita registrada correctamente.', 201)
            : $this->successResponse($visita, 'La visita ya estaba registrada.', 200);
    }

    public function show(Request $request, Visita $visita): JsonResponse
    {
        if ($visita->user_id !== $request->user()->id && ! $request->user()->esAdministrador()) {
            return $this->errorResponse('No tiene acceso a esta visita.', 403);
        }

        $visita->load(['institucion', 'docente']);

        return $this->successResponse($visita);
    }
}

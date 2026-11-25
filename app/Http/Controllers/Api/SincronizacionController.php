<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegistrarVisita;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitaRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Sprint 6 - Sincronización asíncrona de datos (RF-02).
 *
 * La app envía en un solo paquete JSON las visitas que guardó sin conexión.
 * Cada visita se procesa por separado: las válidas quedan sincronizadas, las
 * repetidas (mismo uuid_offline) no se duplican y las que el servidor rechaza
 * vuelven con el motivo para que el asesor las revise, sin bloquear al resto.
 */
class SincronizacionController extends Controller
{
    use ApiResponse;

    public const MAXIMO_POR_PAQUETE = 50;

    /**
     * POST /api/v1/sincronizacion  { "visitas": [ {...uuid_offline, fecha_visita, muestras...}, ... ] }
     */
    public function store(Request $request, RegistrarVisita $registrar): JsonResponse
    {
        $request->validate([
            'visitas' => ['required', 'array', 'min:1', 'max:'.self::MAXIMO_POR_PAQUETE],
            'visitas.*' => ['array'],
            'visitas.*.uuid_offline' => ['required', 'uuid', 'distinct'],
        ]);

        $resultados = [];

        foreach ($request->input('visitas') as $datos) {
            $resultados[] = $this->procesar($request, $registrar, $datos);
        }

        $conteo = collect($resultados)->countBy('estado');

        return $this->successResponse([
            'resultados' => $resultados,
            'sincronizadas' => ($conteo['sincronizado'] ?? 0) + ($conteo['duplicado'] ?? 0),
            'rechazadas' => $conteo['rechazado'] ?? 0,
            'hora_servidor' => now()->toIso8601String(),
        ], 'Sincronización procesada.');
    }

    private function procesar(Request $request, RegistrarVisita $registrar, array $datos): array
    {
        $uuid = $datos['uuid_offline'];
        // capturada_offline indica si el teléfono estaba sin señal al registrarla (se muestra
        // en el panel). Para validar la fecha, todo lo que llega en el paquete usa la ventana
        // offline: una visita capturada con señal pudo quedar en cola si falló el envío.
        $datos['capturada_offline'] = $datos['capturada_offline'] ?? true;
        $reglas = VisitaRequest::reglas(['capturada_offline' => true] + $datos);

        $validador = Validator::make($datos, $reglas, (new VisitaRequest)->messages(), (new VisitaRequest)->attributes());

        try {
            [$visita, $creada] = $registrar($request->user(), $validador->validate(), 'movil');
        } catch (ValidationException $e) {
            return ['uuid_offline' => $uuid, 'estado' => 'rechazado', 'visita_id' => null, 'errores' => collect($e->errors())->flatten()->values()->all()];
        } catch (HttpException $e) {
            return ['uuid_offline' => $uuid, 'estado' => 'rechazado', 'visita_id' => null, 'errores' => [$e->getMessage()]];
        }

        return [
            'uuid_offline' => $uuid,
            'estado' => $creada ? 'sincronizado' : 'duplicado',
            'visita_id' => $visita->id,
            'errores' => [],
        ];
    }
}

<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Visita;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Registra una visita comercial (Sprint 4, RF-01).
 *
 * Si el dispositivo envía un uuid_offline que ya existe para el mismo asesor,
 * devuelve la visita existente en lugar de duplicarla. Esto deja lista la
 * base para la sincronización offline del Sprint 6.
 */
class RegistrarVisita
{
    /**
     * @return array{0: Visita, 1: bool} La visita y si fue creada ahora.
     */
    public function __invoke(User $asesor, array $datos, string $origen): array
    {
        $uuid = $datos['uuid_offline'] ?? (string) Str::uuid();

        $existente = Visita::where('uuid_offline', $uuid)->first();
        if ($existente) {
            abort_unless($existente->user_id === $asesor->id, 409, 'El identificador de la visita ya está en uso.');

            return [$existente, false];
        }

        $visita = Visita::create([
            'uuid_offline' => $uuid,
            'user_id' => $asesor->id,
            'institucion_id' => $datos['institucion_id'],
            'docente_id' => $datos['docente_id'],
            // Se guarda en la zona horaria de la aplicación aunque el móvil envíe otra.
            'fecha_visita' => Carbon::parse($datos['fecha_visita'])->setTimezone(config('app.timezone')),
            'nivel_interes' => $datos['nivel_interes'],
            'estado_adopcion' => $datos['estado_adopcion'],
            'acuerdos' => $datos['acuerdos'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'latitud' => $datos['latitud'] ?? null,
            'longitud' => $datos['longitud'] ?? null,
            'origen' => $origen,
            'estado_sincronizacion' => 'sincronizado',
        ]);

        return [$visita, true];
    }
}

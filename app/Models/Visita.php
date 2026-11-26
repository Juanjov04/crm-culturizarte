<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visita extends Model
{
    public const NIVELES_INTERES = ['bajo', 'medio', 'alto'];

    public const ESTADOS_ADOPCION = ['prospecto', 'interesado', 'adoptado', 'desertado'];

    /** Minutos de margen hacia atrás para la fecha de la visita (tiempo de llenado). */
    public const MARGEN_FECHA_MINUTOS = 15;

    /** Días que puede esperar una visita registrada sin conexión para sincronizarse (Sprint 6). */
    public const DIAS_OFFLINE = 7;

    /** Hasta cuántos días hacia el futuro puede programarse la fecha de la visita en la app. */
    public const MAX_DIAS_FUTURO = 365;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha_visita' => 'datetime',
            'sincronizada_en' => 'datetime',
            'capturada_offline' => 'boolean',
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function muestrasEntregadas(): HasMany
    {
        return $this->hasMany(MuestraEntregada::class);
    }
}

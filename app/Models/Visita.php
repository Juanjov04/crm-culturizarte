<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visita extends Model
{
    public const NIVELES_INTERES = ['bajo', 'medio', 'alto'];

    public const ESTADOS_ADOPCION = ['prospecto', 'interesado', 'adoptado', 'desertado'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha_visita' => 'datetime',
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

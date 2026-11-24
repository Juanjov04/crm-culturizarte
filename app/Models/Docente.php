<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Docente extends Model
{
    protected $guarded = [];

    protected $appends = ['nombre_completo'];

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }

    /**
     * RF-03: historial de muestras entregadas al docente (a través de sus visitas).
     */
    public function muestrasEntregadas(): HasManyThrough
    {
        return $this->hasManyThrough(MuestraEntregada::class, Visita::class);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim($this->nombres.' '.$this->apellidos));
    }
}

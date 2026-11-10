<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim($this->nombres.' '.$this->apellidos));
    }
}

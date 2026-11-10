<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institucion extends Model
{
    public const TIPOS = ['Particular', 'Fiscomisional', 'Fiscal', 'Municipal'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function docentes(): HasMany
    {
        return $this->hasMany(Docente::class);
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}

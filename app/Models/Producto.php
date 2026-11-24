<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Texto escolar del catálogo (Sprint 5, RF-03).
 * stock_actual es el stock referencial de bodega: se descuenta al asignar
 * muestras a los asesores (no se gestionan entradas físicas de bodega).
 */
class Producto extends Model
{
    public const SERIES = ['ECA', 'Comprensión Lectora'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'stock_actual' => 'integer',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function muestrasEntregadas(): HasMany
    {
        return $this->hasMany(MuestraEntregada::class);
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(InventarioAsesor::class);
    }

    /**
     * RF-03: el libro debe corresponder a la materia del docente.
     * Se compara el área sin distinguir mayúsculas, tildes ni espacios extra.
     */
    public function esCompatibleCon(Docente $docente): bool
    {
        return self::normalizarArea($this->area) === self::normalizarArea($docente->area_asignatura);
    }

    public static function normalizarArea(?string $area): string
    {
        return (string) Str::of((string) $area)->ascii()->lower()->squish();
    }
}

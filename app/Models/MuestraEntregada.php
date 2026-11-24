<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Muestra de texto escolar entregada a un docente durante una visita (Sprint 5, RF-03).
 */
class MuestraEntregada extends Model
{
    public const FORMATOS = ['fisica', 'digital'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function visita(): BelongsTo
    {
        return $this->belongsTo(Visita::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function getFormatoTextoAttribute(): string
    {
        return $this->formato === 'digital' ? 'Digital' : 'Física';
    }
}

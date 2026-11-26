<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Muestra de texto escolar entregada a un docente durante una visita (Sprint 5, RF-03).
 */
class MuestraEntregada extends Model
{
    /** La editorial solo entrega muestras físicas (no hay libros digitales). */
    public const FORMATOS = ['fisica'];

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

}

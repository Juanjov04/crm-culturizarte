<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada asignación de muestras de bodega a un asesor (Sprint 5).
 */
class AsignacionStock extends Model
{
    protected $table = 'asignaciones_stock';

    protected $guarded = [];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }
}

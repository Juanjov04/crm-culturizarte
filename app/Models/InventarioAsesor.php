<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock de muestras que tiene asignado un asesor por cada texto (Sprint 5, RF-03).
 */
class InventarioAsesor extends Model
{
    protected $table = 'inventario_asesores';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

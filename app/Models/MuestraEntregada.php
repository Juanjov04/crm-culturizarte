<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuestraEntregada extends Model
{
    protected $guarded = [];

    public function visita(): BelongsTo
    {
        return $this->belongsTo(Visita::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

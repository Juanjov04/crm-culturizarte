<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 5 (RF-03): inventario personal de muestras de cada asesor.
     */
    public function up(): void
    {
        Schema::create('inventario_asesores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'producto_id']);
        });

        // Historial de asignaciones de bodega a asesores (trazabilidad del stock).
        Schema::create('asignaciones_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('asesor_id')->constrained('users');
            $table->foreignId('asignado_por')->constrained('users');
            $table->unsignedInteger('cantidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_stock');
        Schema::dropIfExists('inventario_asesores');
    }
};

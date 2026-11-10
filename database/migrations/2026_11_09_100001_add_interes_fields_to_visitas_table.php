<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4 (RF-01): la visita registra el nivel de interés, el estado del
     * docente frente a la adopción de textos y los acuerdos alcanzados.
     */
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->string('nivel_interes', 10)->default('medio');
            $table->string('estado_adopcion', 20)->default('prospecto');
            $table->text('acuerdos')->nullable();
            $table->string('origen', 10)->default('web');
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn(['nivel_interes', 'estado_adopcion', 'acuerdos', 'origen']);
        });
    }
};

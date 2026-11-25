<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 6 (RF-01/RF-02): se registra si la visita se capturó sin conexión
     * y cuándo llegó al servidor central.
     */
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->boolean('capturada_offline')->default(false);
            $table->timestamp('sincronizada_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn(['capturada_offline', 'sincronizada_en']);
        });
    }
};

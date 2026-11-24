<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 5 (RF-03): el catálogo de textos escolares guarda la serie
     * (ECA / Comprensión Lectora), el área con la que es compatible y si
     * está disponible. stock_actual es el stock referencial de bodega.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('serie', 40)->default('Comprensión Lectora');
            $table->string('area', 100)->default('Lengua y Literatura');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['serie', 'area', 'activo']);
        });
    }
};

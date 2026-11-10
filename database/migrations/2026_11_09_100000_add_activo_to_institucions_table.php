<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 3 (RF-05): un colegio puede pasar a inactivo y el sistema
     * debe alertar sobre el estado de sus docentes.
     */
    public function up(): void
    {
        Schema::table('institucions', function (Blueprint $table) {
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('institucions', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};

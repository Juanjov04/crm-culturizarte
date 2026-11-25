<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El kardex de la editorial distingue ediciones del mismo texto y grado
     * (p. ej. "Proyectos por competencias", "Con leyenda de certificado por el MINEDUC").
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('edicion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('edicion');
        });
    }
};

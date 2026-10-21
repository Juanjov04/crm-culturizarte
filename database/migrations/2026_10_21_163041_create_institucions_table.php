<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('institucions', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('codigo_amie')->unique()->nullable();
        $table->string('tipo')->default('Particular'); // Particular, Fiscomisional, Fiscal
        $table->string('ciudad');
        $table->string('direccion');
        $table->string('telefono')->nullable();
        $table->string('email_contacto')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institucions');
    }
};

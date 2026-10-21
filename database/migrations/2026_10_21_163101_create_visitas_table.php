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
    Schema::create('visitas', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid_offline')->unique()->nullable();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('institucion_id')->constrained('institucions');
        $table->foreignId('docente_id')->nullable()->constrained('docentes');
        $table->dateTime('fecha_visita');
        $table->text('observaciones')->nullable();
        $table->decimal('latitud', 10, 7)->nullable();
        $table->decimal('longitud', 10, 7)->nullable();
        $table->string('estado_sincronizacion')->default('sincronizado');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};

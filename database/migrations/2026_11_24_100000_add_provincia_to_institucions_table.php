<?php

use App\Support\Ecuador;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observación del jurado: provincia y ciudad se eligen de una lista.
     * Las ciudades ya registradas a mano ("QUITO", "quito") se corrigen al
     * nombre oficial y se les asigna su provincia cuando coinciden.
     */
    public function up(): void
    {
        Schema::table('institucions', function (Blueprint $table) {
            $table->string('provincia', 60)->nullable();
        });

        foreach (DB::table('institucions')->get(['id', 'ciudad']) as $institucion) {
            if ($encontrada = Ecuador::buscarCiudad($institucion->ciudad)) {
                [$provincia, $canton] = $encontrada;
                DB::table('institucions')->where('id', $institucion->id)
                    ->update(['provincia' => $provincia, 'ciudad' => $canton]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('institucions', function (Blueprint $table) {
            $table->dropColumn('provincia');
        });
    }
};

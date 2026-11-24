<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 5 (RF-03): la muestra puede ser física (descuenta stock) o digital.
     */
    public function up(): void
    {
        Schema::table('muestra_entregadas', function (Blueprint $table) {
            $table->string('formato', 10)->default('fisica');
        });
    }

    public function down(): void
    {
        Schema::table('muestra_entregadas', function (Blueprint $table) {
            $table->dropColumn('formato');
        });
    }
};

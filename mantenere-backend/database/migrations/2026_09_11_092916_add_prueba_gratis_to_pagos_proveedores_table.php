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
        Schema::table('pagos_proveedores', function (Blueprint $table) {
            $table->boolean('es_prueba_gratis')->default(false)->after('notas');
            $table->integer('dias_prueba')->default(180)->after('es_prueba_gratis');
            $table->timestamp('prueba_inicio_at')->nullable()->after('dias_prueba');
            $table->timestamp('prueba_fin_at')->nullable()->after('prueba_inicio_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_proveedores', function (Blueprint $table) {
            $table->dropColumn(['es_prueba_gratis', 'dias_prueba', 'prueba_inicio_at', 'prueba_fin_at']);
        });
    }
};

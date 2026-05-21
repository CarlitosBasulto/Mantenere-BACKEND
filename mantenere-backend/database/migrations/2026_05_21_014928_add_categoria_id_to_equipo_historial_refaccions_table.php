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
        Schema::table('equipo_historial_refaccions', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->after('levantamiento_equipo_id');
            $table->foreign('categoria_id')->references('id')->on('categorias_equipos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipo_historial_refaccions', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};

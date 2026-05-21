<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimiento_solicitudes', function (Blueprint $table) {
            // Nullable so existing records aren't broken
            $table->unsignedBigInteger('equipo_id')->nullable()->after('negocio_id');
            $table->foreign('equipo_id')
                  ->references('id')
                  ->on('levantamiento_equipos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mantenimiento_solicitudes', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropColumn('equipo_id');
        });
    }
};

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
        Schema::create('mantenimiento_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_solicitud_id')->constrained('mantenimiento_solicitudes')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained('trabajadores')->cascadeOnDelete();
            
            $table->text('diagnostico_final')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('materiales_usados')->nullable();
            
            // Archivos o Json arrays
            $table->json('evidencia_antes')->nullable();
            $table->json('evidencia_durante')->nullable();
            $table->json('evidencia_despues')->nullable();
            
            // Archivo PDF / Firma final que sube el técnico
            $table->string('archivo_firmado')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_reportes');
    }
};

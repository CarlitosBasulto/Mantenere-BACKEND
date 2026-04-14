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
        Schema::create('mantenimiento_visitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_solicitud_id')->constrained('mantenimiento_solicitudes')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained('trabajadores')->cascadeOnDelete();
            
            $table->text('diagnostico')->nullable();
            $table->string('pieza_danada')->nullable();
            $table->text('reparacion_necesaria')->nullable();
            
            // Puede ser la cantidad dada por el técnico inicialmente
            $table->decimal('cotizacion_tecnico', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_visitas');
    }
};

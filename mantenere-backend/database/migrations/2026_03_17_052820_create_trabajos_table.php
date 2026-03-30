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
        Schema::create('trabajos', function (Blueprint $table) {
            $table->id();

            // Datos generales
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('prioridad', ['Alta', 'Media', 'Baja'])->default('Media');

            // 🔥 El estado como string para soportar "Cotización Enviada", "Asignado", etc.
            $table->string('estado')->default('Pendiente');

            // 🔥 Nuevos campos que usa tu React
            $table->string('tipo')->nullable();
            $table->date('fechaAsignada')->nullable();
            $table->time('horaAsignada')->nullable();
            $table->boolean('visitado')->default(false);

            // Relaciones con técnicos y negocios
            $table->foreignId('trabajador_id')->nullable()->constrained('trabajadores')->nullOnDelete();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();

            $table->date('fecha_programada')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajos');
    }
};

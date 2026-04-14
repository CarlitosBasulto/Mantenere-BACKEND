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
        Schema::create('mantenimiento_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('levantamiento_equipo_id')->constrained('levantamiento_equipos')->cascadeOnDelete();
            $table->text('descripcion_problema');
            
            // Estado principal
            $table->enum('estado', [
                'Pendiente', 
                'Visita Asignada', 
                'Diagnosticado', 
                'Cotizado al Cliente', 
                'Aprobado por Cliente', 
                'Cotización Aceptada',
                'Cotización Rechazada',
                'Trabajo Asignado', 
                'Finalizado'
            ])->default('Pendiente');

            // Enlaces a los trabajos en el sistema (asignaciones a técnicos)
            $table->foreignId('visita_trabajo_id')->nullable()->constrained('trabajos')->nullOnDelete();
            $table->foreignId('reparacion_trabajo_id')->nullable()->constrained('trabajos')->nullOnDelete();

            // Datos de la cotización ajustada por admin
            $table->decimal('admin_cotizacion', 10, 2)->nullable();
            $table->string('admin_cotizacion_pdf')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_solicitudes');
    }
};

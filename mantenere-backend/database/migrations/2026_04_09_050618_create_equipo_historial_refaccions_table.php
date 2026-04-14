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
        Schema::dropIfExists('equipo_historial_refaccions');
        Schema::create('equipo_historial_refaccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('levantamiento_equipo_id')->nullable()->constrained('levantamiento_equipos')->onDelete('cascade');
            $table->string('pieza');
            $table->integer('cantidad')->default(1);
            $table->decimal('costo_estimado', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_historial_refaccions');
    }
};

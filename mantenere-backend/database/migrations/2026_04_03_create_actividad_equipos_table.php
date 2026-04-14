<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            
            $table->string('tipo')->nullable(); // Instalación, Mantenimiento, etc.
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->integer('piezas')->default(1);
            $table->string('garantia')->nullable(); // Meses o fecha
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_equipos');
    }
};

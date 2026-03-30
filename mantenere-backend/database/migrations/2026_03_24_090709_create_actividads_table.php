<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            // Esto vincula la actividad con la visita exacta que el admin le asignó al técnico
            $table->foreignId('trabajo_id')->constrained()->cascadeOnDelete();

            $table->string('tipo'); // Ejemplo: 'Plomería', 'Electricidad'
            $table->text('descripcion'); // Lo que escribió el técnico

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};

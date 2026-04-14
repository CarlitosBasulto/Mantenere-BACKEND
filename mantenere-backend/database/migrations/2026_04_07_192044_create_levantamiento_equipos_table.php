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
        Schema::create('levantamiento_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('levantamiento_area_id')->constrained('levantamiento_areas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('marca');
            $table->string('modelo');
            $table->string('serie')->nullable();
            $table->string('anioFabricacion')->nullable();
            $table->string('anioUso')->nullable();
            $table->longText('foto')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levantamiento_equipos');
    }
};

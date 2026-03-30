<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre de la sucursal/negocio
            $table->enum('tipo', ['FC', 'FS', 'MALL', 'W/M'])->default('FC');
            $table->string('encargado')->nullable(); // Dueño o encargado principal

            // Relación con el usuario cliente que lo registra
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Ubicación Estándar
            $table->string('nombrePlaza')->nullable();
            $table->string('estado')->default('Yucatán');
            $table->string('ciudad')->default('Mérida');
            $table->string('calle')->nullable();
            $table->string('numero')->nullable();
            $table->string('colonia')->nullable();
            $table->string('cp')->nullable();

            // Campos específicos de FS (Free Standing)
            $table->string('referencia')->nullable();

            // Campos específicos W/M (Walmart)
            $table->string('manzana')->nullable();
            $table->string('lote')->nullable();
            $table->string('calleAv')->nullable();

            // Contacto
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();

            // Imagen de Perfil (Guardaremos la url/path de la imagen)
            $table->longText('imagenPerfil')->nullable();

            // Estatus del negocio (Aprobado, En Espera, etc.) para el Admin
            $table->string('estado_aprobacion')->default('En Espera');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};

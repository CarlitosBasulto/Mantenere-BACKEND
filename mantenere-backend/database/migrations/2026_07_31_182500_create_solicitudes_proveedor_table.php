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
        Schema::create('solicitudes_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre_empresa');
            $table->string('telefono')->nullable();
            $table->string('identificacion_proveedor_url')->nullable();
            $table->enum('estado', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->longText('escuadron_json')->nullable(); // JSON con técnicos del escuadrón e imágenes de INE
            $table->timestamps();
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->nullable()->after('admin_autonomo_id');
            $table->boolean('es_proveedor')->default(false)->after('proveedor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_proveedor');

        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropColumn(['proveedor_id', 'es_proveedor']);
        });
    }
};

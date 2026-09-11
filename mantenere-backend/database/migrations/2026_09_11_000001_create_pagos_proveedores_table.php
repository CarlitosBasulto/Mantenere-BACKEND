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
        Schema::create('pagos_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('trabajador_id')->nullable();
            $table->string('nombre_empresa');
            $table->string('telefono')->nullable();
            $table->decimal('monto', 10, 2)->default(1499.00);
            $table->string('banco_origen')->nullable();
            $table->string('folio_referencia')->nullable();
            $table->date('fecha_transferencia')->nullable();
            $table->string('comprobante_url')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();

            $table->foreign('trabajador_id')->references('id')->on('trabajadores')->nullOnDelete();
            $table->foreign('aprobado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_proveedores');
    }
};

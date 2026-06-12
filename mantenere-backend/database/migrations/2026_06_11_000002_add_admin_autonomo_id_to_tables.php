<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega admin_autonomo_id a las tablas clave para aislar
     * los datos de cada Admin Autónomo del sistema principal.
     *
     * NULL = pertenece al sistema principal (Admin/Root)
     * ID  = pertenece al Admin Autónomo con ese user_id
     */
    public function up(): void
    {
        // Negocios/sucursales
        if (!Schema::hasColumn('negocios', 'admin_autonomo_id')) {
            Schema::table('negocios', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_autonomo_id')->nullable()->after('id');
            });
        }

        // Trabajadores (técnicos)
        if (!Schema::hasColumn('trabajadores', 'admin_autonomo_id')) {
            Schema::table('trabajadores', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_autonomo_id')->nullable()->after('id');
            });
        }

        // Trabajos (solicitudes/órdenes de trabajo)
        if (!Schema::hasColumn('trabajos', 'admin_autonomo_id')) {
            Schema::table('trabajos', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_autonomo_id')->nullable()->after('id');
            });
        }

        // Cotizaciones
        if (!Schema::hasColumn('cotizaciones', 'admin_autonomo_id')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_autonomo_id')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('admin_autonomo_id');
        });
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropColumn('admin_autonomo_id');
        });
        Schema::table('trabajos', function (Blueprint $table) {
            $table->dropColumn('admin_autonomo_id');
        });
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('admin_autonomo_id');
        });
    }
};

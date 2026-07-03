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
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->unsignedBigInteger('creador_id')->nullable()->after('admin_autonomo_id');
            // Backfill: asignamos como creador al mismo admin_autonomo_id si existe
        });
        
        \Illuminate\Support\Facades\DB::statement('UPDATE trabajadores SET creador_id = admin_autonomo_id WHERE admin_autonomo_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropColumn('creador_id');
        });
    }
};

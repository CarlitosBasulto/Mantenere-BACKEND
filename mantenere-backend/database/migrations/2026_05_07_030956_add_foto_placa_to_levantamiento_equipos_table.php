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
        Schema::table('levantamiento_equipos', function (Blueprint $table) {
            $table->longText('fotoPlaca')->nullable()->after('foto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('levantamiento_equipos', function (Blueprint $table) {
            $table->dropColumn('fotoPlaca');
        });
    }
};

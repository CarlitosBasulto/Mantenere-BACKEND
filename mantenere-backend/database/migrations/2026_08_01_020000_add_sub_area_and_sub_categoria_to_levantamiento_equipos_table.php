<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('levantamiento_equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('levantamiento_equipos', 'subAreaId')) {
                $table->string('subAreaId')->nullable()->after('categoria_id');
            }
            if (!Schema::hasColumn('levantamiento_equipos', 'nombreSubArea')) {
                $table->string('nombreSubArea')->nullable()->after('subAreaId');
            }
            if (!Schema::hasColumn('levantamiento_equipos', 'subCategoria')) {
                $table->string('subCategoria')->nullable()->after('nombreSubArea');
            }
        });
    }

    public function down(): void
    {
        Schema::table('levantamiento_equipos', function (Blueprint $table) {
            $table->dropColumn(['subAreaId', 'nombreSubArea', 'subCategoria']);
        });
    }
};

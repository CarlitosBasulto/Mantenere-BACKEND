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
        Schema::table('negocios', function (Blueprint $table) {
            $table->string('gerente')->nullable()->after('cp');
            $table->string('telefonoGerente')->nullable()->after('gerente');
            $table->string('subgerente')->nullable()->after('telefonoGerente');
            $table->string('telefonoSubgerente')->nullable()->after('subgerente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['gerente', 'telefonoGerente', 'subgerente', 'telefonoSubgerente']);
        });
    }
};

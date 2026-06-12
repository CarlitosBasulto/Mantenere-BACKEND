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
        // Alter table using raw DB statement to avoid enum to string doctrine issues
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE negocios MODIFY COLUMN tipo VARCHAR(255) DEFAULT 'FC'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE negocios MODIFY COLUMN tipo ENUM('FC', 'FS', 'MALL', 'W/M') DEFAULT 'FC'");
    }
};

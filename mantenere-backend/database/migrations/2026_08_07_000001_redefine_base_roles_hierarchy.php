<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Redefine la jerarquía de roles de la app base:
 *
 *  0 → root       (sin cambio)
 *  1 → Admin      (sin cambio)
 *  2 → Cliente    (era 3 o 4 según migraciones anteriores)
 *  3 → Trabajador (era 4 o 5 según migraciones anteriores)
 *
 * El rol Sub-Admin se elimina completamente.
 * Los roles del ecosistema autónomo (admin-autonomo, gerente-general, encargado)
 * no se tocan en esta migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar Sub-Admin
        DB::table('roles')->where('name', 'Sub-Admin')->delete();

        // 2. Reordenar jerarquía base
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 2]);
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 3]);
    }

    public function down(): void
    {
        // Revertir: restaurar jerarquía anterior y recrear Sub-Admin
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 4]);
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 5]);

        $subAdminExists = DB::table('roles')->where('name', 'Sub-Admin')->exists();
        if (!$subAdminExists) {
            DB::table('roles')->insert([
                'name'            => 'Sub-Admin',
                'hierarchy_level' => 3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
};

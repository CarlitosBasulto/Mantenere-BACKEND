<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cambios:
     * - Inserta el rol Sub-Admin (hierarchy_level=2) si no existe.
     * - Sube Cliente de hierarchy_level 2 → 3.
     * - Sube Trabajador de hierarchy_level 3 → 4.
     * - Asigna admin2@mantenere.com al nuevo rol Sub-Admin.
     *
     * NO toca ningún otro usuario ni registro de la app.
     */
    public function up(): void
    {
        // 1. Insertar el rol Sub-Admin solo si no existe
        $subAdminExists = DB::table('roles')->where('name', 'Sub-Admin')->exists();
        if (!$subAdminExists) {
            DB::table('roles')->insert([
                'name'            => 'Sub-Admin',
                'hierarchy_level' => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // 2. Reordenar jerarquías: Cliente 2→3, Trabajador 3→4
        //    Primero subimos Trabajador para evitar conflictos de valor único si hubiera index
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 4]);
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 3]);

        // 3. Asignar admin2@mantenere.com al rol Sub-Admin
        $subAdminRole = DB::table('roles')->where('name', 'Sub-Admin')->first();
        if ($subAdminRole) {
            DB::table('users')
                ->where('email', 'admin2@mantenere.com')
                ->update(['role_id' => $subAdminRole->id]);
        }
    }

    /**
     * Rollback:
     * - Devuelve admin2 al rol Admin (id=1).
     * - Restaura jerarquías de Cliente y Trabajador.
     * - Elimina el rol Sub-Admin.
     */
    public function down(): void
    {
        // Revertir admin2 al rol Admin
        DB::table('users')
            ->where('email', 'admin2@mantenere.com')
            ->update(['role_id' => 1]);

        // Restaurar jerarquías originales
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 2]);
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 3]);

        // Eliminar Sub-Admin
        DB::table('roles')->where('name', 'Sub-Admin')->delete();
    }
};

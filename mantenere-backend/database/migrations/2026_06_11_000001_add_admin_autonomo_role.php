<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Inserta el rol 'admin-autonomo' con hierarchy_level = 2.
     * Sub-Admin sube a level 3 para cederle el espacio.
     * Los demás roles se reordenan en consecuencia.
     */
    public function up(): void
    {
        // Reordenar hacia abajo para hacer espacio en level 2
        // encargado: 5 -> 6
        DB::table('roles')->where('name', 'encargado')->update(['hierarchy_level' => 6]);
        // Trabajador: 4 -> 5
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 5]);
        // Cliente: 3 -> 4
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 4]);
        // Sub-Admin: 2 -> 3
        DB::table('roles')->where('name', 'Sub-Admin')->update(['hierarchy_level' => 3]);

        // Insertar rol admin-autonomo si no existe
        $exists = DB::table('roles')->where('name', 'admin-autonomo')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name'            => 'admin-autonomo',
                'hierarchy_level' => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Eliminar rol admin-autonomo
        DB::table('roles')->where('name', 'admin-autonomo')->delete();

        // Restaurar jerarquías anteriores
        DB::table('roles')->where('name', 'Sub-Admin')->update(['hierarchy_level' => 2]);
        DB::table('roles')->where('name', 'Cliente')->update(['hierarchy_level' => 3]);
        DB::table('roles')->where('name', 'Trabajador')->update(['hierarchy_level' => 4]);
        DB::table('roles')->where('name', 'encargado')->update(['hierarchy_level' => 5]);
    }
};

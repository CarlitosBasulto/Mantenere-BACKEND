<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar rol 'tecnico-proveedor' (hierarchy_level: 8) si no existe
        $existsProveedor = DB::table('roles')->where('name', 'tecnico-proveedor')->exists();
        if (!$existsProveedor) {
            DB::table('roles')->insert([
                'name'            => 'tecnico-proveedor',
                'hierarchy_level' => 8,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // 2. Agregar rol 'tecnico-cuadrilla' (hierarchy_level: 9) si no existe
        $existsCuadrilla = DB::table('roles')->where('name', 'tecnico-cuadrilla')->exists();
        if (!$existsCuadrilla) {
            DB::table('roles')->insert([
                'name'            => 'tecnico-cuadrilla',
                'hierarchy_level' => 9,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['tecnico-proveedor', 'tecnico-cuadrilla'])->delete();
    }
};

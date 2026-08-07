<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Redefine todos los roles del sistema con nombres y jerarquías limpias:
 *
 * BASE:
 *   0 → root               (sin cambio)
 *   1 → Admin              (sin cambio)
 *   2 → Cliente            (sin cambio)
 *   3 → tecnico-normal     (antes: Trabajador)
 *
 * AUTÓNOMO:
 *   4 → propietario-autonomo   (antes: admin-autonomo)
 *   5 → administrador-general  (antes: gerente-general)
 *   6 → gerente-sucursal       (antes: encargado)
 *   7 → tecnico-autonomo       (nuevo)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── BASE ─────────────────────────────────────────────────────────────
        // Renombrar Trabajador → tecnico-normal (level 3 sin cambio)
        DB::table('roles')
            ->where('name', 'Trabajador')
            ->update(['name' => 'tecnico-normal']);

        // ── AUTÓNOMO ─────────────────────────────────────────────────────────
        // admin-autonomo → propietario-autonomo, level 4
        DB::table('roles')
            ->where('name', 'admin-autonomo')
            ->update(['name' => 'propietario-autonomo', 'hierarchy_level' => 4]);

        // gerente-general → administrador-general, level 5
        DB::table('roles')
            ->where('name', 'gerente-general')
            ->update(['name' => 'administrador-general', 'hierarchy_level' => 5]);

        // encargado → gerente-sucursal, level 6
        DB::table('roles')
            ->where('name', 'encargado')
            ->update(['name' => 'gerente-sucursal', 'hierarchy_level' => 6]);

        // Insertar nuevo rol tecnico-autonomo (level 7)
        $exists = DB::table('roles')->where('name', 'tecnico-autonomo')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name'            => 'tecnico-autonomo',
                'hierarchy_level' => 7,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Revertir tecnico-normal → Trabajador
        DB::table('roles')
            ->where('name', 'tecnico-normal')
            ->update(['name' => 'Trabajador']);

        // Revertir propietario-autonomo → admin-autonomo
        DB::table('roles')
            ->where('name', 'propietario-autonomo')
            ->update(['name' => 'admin-autonomo', 'hierarchy_level' => 2]);

        // Revertir administrador-general → gerente-general
        DB::table('roles')
            ->where('name', 'administrador-general')
            ->update(['name' => 'gerente-general', 'hierarchy_level' => 2]);

        // Revertir gerente-sucursal → encargado
        DB::table('roles')
            ->where('name', 'gerente-sucursal')
            ->update(['name' => 'encargado', 'hierarchy_level' => 6]);

        // Eliminar tecnico-autonomo
        DB::table('roles')->where('name', 'tecnico-autonomo')->delete();
    }
};

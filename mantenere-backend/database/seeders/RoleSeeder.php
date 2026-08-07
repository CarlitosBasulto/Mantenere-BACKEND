<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ── BASE ──────────────────────────────────────────
        // 0 = root  (creado por migración add_root_role)
        // 1 = Admin
        // 2 = Cliente
        // 3 = tecnico-normal
        Role::firstOrCreate(['name' => 'Admin'],          ['hierarchy_level' => 1]);
        Role::firstOrCreate(['name' => 'Cliente'],        ['hierarchy_level' => 2]);
        Role::firstOrCreate(['name' => 'tecnico-normal'], ['hierarchy_level' => 3]);

        // ── AUTÓNOMO ──────────────────────────────────────
        // 4 = propietario-autonomo
        // 5 = administrador-general
        // 6 = gerente-sucursal
        // 7 = tecnico-autonomo
        Role::firstOrCreate(['name' => 'propietario-autonomo'],  ['hierarchy_level' => 4]);
        Role::firstOrCreate(['name' => 'administrador-general'], ['hierarchy_level' => 5]);
        Role::firstOrCreate(['name' => 'gerente-sucursal'],      ['hierarchy_level' => 6]);
        Role::firstOrCreate(['name' => 'tecnico-autonomo'],      ['hierarchy_level' => 7]);
    }
}

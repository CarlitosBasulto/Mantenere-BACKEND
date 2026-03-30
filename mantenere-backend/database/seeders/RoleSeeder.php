<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create([
            'name' => 'Admin',
            'hierarchy_level' => 1
        ]);

        Role::create([
            'name' => 'Cliente',
            'hierarchy_level' => 2
        ]);

        Role::create([
            'name' => 'Trabajador',
            'hierarchy_level' => 3
        ]);
    }
}

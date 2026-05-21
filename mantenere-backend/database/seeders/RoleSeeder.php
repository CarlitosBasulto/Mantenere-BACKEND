<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'Admin'],
            ['hierarchy_level' => 1]
        );

        Role::firstOrCreate(
            ['name' => 'Cliente'],
            ['hierarchy_level' => 3]
        );

        Role::firstOrCreate(
            ['name' => 'Trabajador'],
            ['hierarchy_level' => 4]
        );
    }
}

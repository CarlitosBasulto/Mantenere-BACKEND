<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::updateOrCreate(
            ['email' => 'root@mantenere.com'],
            [
                'name' => 'Root',
                'password' => Hash::make('root123'),
                'role_id' => 1, // admin
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@mantenere.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role_id' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente@mantenere.com'],
            [
                'name' => 'Cliente',
                'password' => Hash::make('cliente123'),
                'role_id' => 2, // cliente
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente2@mantenere.com'],
            [
                'name' => 'Cliente 2',
                'password' => Hash::make('cliente123'),
                'role_id' => 2, // cliente
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente3@mantenere.com'],
            [
                'name' => 'Cliente 3',
                'password' => Hash::make('cliente123'),
                'role_id' => 2, // cliente
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente4@mantenere.com'],
            [
                'name' => 'Cliente 4',
                'password' => Hash::make('cliente123'),
                'role_id' => 2, // cliente
            ]
        );

        User::updateOrCreate(
            ['email' => 'trabajador@mantenere.com'],
            [
                'name' => 'Trabajador',
                'password' => Hash::make('trabajador123'),
                'role_id' => 3, // trabajador
            ]
        );
    }
}

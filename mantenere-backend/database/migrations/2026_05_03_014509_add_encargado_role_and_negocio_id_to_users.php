<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar el rol 'encargado' si no existe
        $roleExists = DB::table('roles')->where('name', 'encargado')->exists();
        if (!$roleExists) {
            DB::table('roles')->insert([
                'name' => 'encargado',
                'hierarchy_level' => 5, // Un nivel por debajo de técnico o cliente
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // 2. Agregar la columna negocio_id a la tabla users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'negocio_id')) {
                $table->unsignedBigInteger('negocio_id')->nullable()->after('role_id');
                $table->foreign('negocio_id')->references('id')->on('negocios')->onDelete('set null');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'negocio_id')) {
                $table->dropForeign(['negocio_id']);
                $table->dropColumn('negocio_id');
            }
        });
        DB::table('roles')->where('name', 'encargado')->delete();
    }
};

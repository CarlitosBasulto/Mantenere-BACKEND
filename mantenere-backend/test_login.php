<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::with('role')->find(18);
$roleName = strtolower($user->role->name);
$effectiveAdminAutonomoId = $user->admin_autonomo_id;

if (!$effectiveAdminAutonomoId) {
    if ($roleName === 'encargado' && $user->negocio_id) {
        $negocio = \App\Models\Negocio::find($user->negocio_id);
        if ($negocio) {
            $effectiveAdminAutonomoId = $negocio->admin_autonomo_id;
        }
    }
}
echo json_encode(['role'=>$roleName, 'eff'=>$effectiveAdminAutonomoId]);

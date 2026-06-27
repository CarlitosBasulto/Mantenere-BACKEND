<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('name', 'like', '%Diego Basulto%')->first();
$negocio = App\Models\Negocio::find($user->negocio_id);

echo json_encode([
    'id' => $user->id, 
    'user_admin_autonomo_id' => $user->admin_autonomo_id,
    'negocio_id' => $user->negocio_id, 
    'negocio_admin_autonomo_id' => $negocio ? $negocio->admin_autonomo_id : null
]);

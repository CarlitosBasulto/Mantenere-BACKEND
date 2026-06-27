<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(16);
echo json_encode(['id'=>$u->id, 'cv_url'=>$u->cv_url]);

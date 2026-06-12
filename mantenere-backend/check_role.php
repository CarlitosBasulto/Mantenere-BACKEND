<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 
$user = \App\Models\User::with('role')->find(16);
echo "Frontend Role would be: " . strtolower($user->role->name);

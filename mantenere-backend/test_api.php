<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login as user 18 (encargado)
$encargado = App\Models\User::find(18);
$token = $encargado->createToken('api-token')->plainTextToken;

// Now make a curl request to 127.0.0.1:8085/api/users/16
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8085/api/users/16');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . explode('|', $token)[1],
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpcode\n";
echo "RESPONSE: $response\n";

<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

try {
    echo "Starting upload...\n";
    // Create a dummy image file
    $tmpFile = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    
    $result = cloudinary()->uploadApi()->upload($tmpFile);
    echo "URL: " . $result['secure_url'] . "\n";
    unlink($tmpFile);
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

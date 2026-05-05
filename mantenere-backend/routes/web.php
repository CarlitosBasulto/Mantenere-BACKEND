<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta de rescate para servir imágenes de Storage en entornos como Railway
Route::get('/storage/uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/uploads/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    
    $mimeType = \Illuminate\Support\Facades\File::mimeType($path);
    return response()->file($path, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400'
    ]);
});

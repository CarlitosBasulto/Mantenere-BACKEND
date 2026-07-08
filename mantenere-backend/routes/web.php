<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-fotos', function () {
    $trabajos = \App\Models\Trabajo::orderBy('id', 'desc')->take(5)->get(['id', 'titulo', 'foto_url']);
    return response()->json($trabajos);
});

// Ruta de rescate universal para servir TODO el contenido de storage/app/public en entornos como Railway
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    $mimeType = \Illuminate\Support\Facades\File::mimeType($fullPath);
    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400'
    ]);
})->where('path', '.*');

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-fotos', function () {
    $trabajos = \App\Models\Trabajo::orderBy('id', 'desc')->take(5)->get(['id', 'titulo', 'foto_url']);
    return response()->json($trabajos);
});

Route::get('/debug-dir', function () {
    $dir = storage_path('app/public/trabajos/fotos');
    if (!is_dir($dir)) return 'No dir: ' . $dir;
    return response()->json(array_diff(scandir($dir), array('.', '..')));
});

Route::get('/debug-create', function () {
    $path = 'trabajos/fotos/dummy.txt';
    Illuminate\Support\Facades\Storage::disk('public')->put($path, 'dummy content');
    return response()->json([
        'path' => $path,
        'url' => asset('storage/' . $path),
        'full_path' => storage_path('app/public/' . $path),
        'exists' => file_exists(storage_path('app/public/' . $path))
    ]);
});

// Ruta de rescate universal para servir TODO el contenido de storage/app/public en entornos como Railway
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        return response()->json([
            'error' => 'File not found',
            'path_requested' => $path,
            'full_path_checked' => $fullPath,
            'storage_app_public_exists' => is_dir(storage_path('app/public')),
            'trabajos_fotos_exists' => is_dir(storage_path('app/public/trabajos/fotos')),
        ], 404);
    }
    
    $mimeType = \Illuminate\Support\Facades\File::mimeType($fullPath);
    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400'
    ]);
})->where('path', '.*');

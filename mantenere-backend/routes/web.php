<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta ligera para keep-alive (sin DB)
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toISOString()]);
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

// The custom rescue route was removed because we now use Laravel 11's native 'serve' => true in filesystems.php

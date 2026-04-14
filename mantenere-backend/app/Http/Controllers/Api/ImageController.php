<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    /**
     * Sube una imagen al servidor y devuelve su URL pública.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Máx 5MB
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            
            // Generar nombre único para la imagen
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            
            // Guardar en storage/app/public/uploads
            $path = $file->storeAs('uploads', $filename, 'public');
            
            // Generar URL completa
            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'Imagen subida correctamente',
                'url' => $url,
                'path' => $path
            ], 200);
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }
}

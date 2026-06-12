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
            
            try {
                if (env('CLOUDINARY_URL')) {
                    // Subir directamente a Cloudinary
                    $result = cloudinary()->uploadApi()->upload($file->getRealPath());
                    $uploadedFileUrl = $result['secure_url'];
                } else {
                    // Fallback local si no hay Cloudinary
                    $path = $file->store('uploads', 'public');
                    $uploadedFileUrl = url('storage/' . $path);
                }

                return response()->json([
                    'message' => 'Imagen subida correctamente',
                    'url' => $uploadedFileUrl,
                    'path' => $uploadedFileUrl
                ], 200);
            } catch (\Exception $e) {
                // Si Cloudinary falla por alguna razón, usar local
                try {
                    $path = $file->store('uploads', 'public');
                    $uploadedFileUrl = url('storage/' . $path);
                    return response()->json([
                        'message' => 'Imagen subida localmente (Cloudinary falló)',
                        'url' => $uploadedFileUrl,
                        'path' => $uploadedFileUrl
                    ], 200);
                } catch (\Exception $e2) {
                    return response()->json([
                        'message' => 'Error al subir la imagen',
                        'error' => $e->getMessage() . ' | ' . $e2->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }
}

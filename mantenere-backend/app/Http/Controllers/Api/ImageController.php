<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
                // Subir directamente a Cloudinary
                $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();

                return response()->json([
                    'message' => 'Imagen subida correctamente',
                    'url' => $uploadedFileUrl,
                    'path' => $uploadedFileUrl // Se mantiene path igual que url por compatibilidad
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al subir la imagen a la nube',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }
}

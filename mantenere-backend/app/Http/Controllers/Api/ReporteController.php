<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Models\ImagenReporte;
use App\Models\Trabajo;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    // Obtener el reporte de un trabajo en específico (Para AdminReporte.tsx)
    public function showByTrabajo($trabajo_id)
    {
        $reporte = Reporte::with('imagenes')->where('trabajo_id', $trabajo_id)->first();

        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }

        return response()->json($reporte);
    }

    // Crear un reporte nuevo (Cuando el Técnico envía el formulario de finalización)
    public function store(Request $request)
    {
        // 1. Validar los datos de texto
        $request->validate([
            'trabajo_id' => 'required|exists:trabajos,id',
        ]);

        // 2. Crear o actualizar el Reporte General
        $reporte = Reporte::updateOrCreate(
        ['trabajo_id' => $request->trabajo_id],
        [
            'descripcion' => $request->descripcion,
            'solucion' => $request->solucion,
            'fecha' => $request->fecha ?? now()
        ]
        );

        // 3. Limpiar imágenes viejas si el reporte se está editando
        $reporte->imagenes()->delete();

        // 4. Guardar las nuevas Imágenes en Base64 enviadas
        if ($request->has('imagenesBase64')) {
            $imagenesPayload = $request->imagenesBase64;

            foreach ($imagenesPayload as $tipo => $base64String) {
                if (!empty($base64String)) {
                    ImagenReporte::create([
                        'reporte_id' => $reporte->id,
                        'ruta' => $base64String // Guardar Base64 en la columna "ruta"
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Reporte y evidencias creadas con éxito.',
            'data' => $reporte->load('imagenes')
        ], 201);
    }


}

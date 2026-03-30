<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    // 🔍 1. Obtener la cotización de un trabajo
    public function showByTrabajo($trabajo_id)
    {
        $cotizacion = Cotizacion::where('trabajo_id', $trabajo_id)->first();
        
        if (!$cotizacion) {
            return response()->json(['message' => 'No hay cotización para este trabajo'], 404);
        }

        return response()->json($cotizacion);
    }

    // ➕ 2. Crear o actualizar la cotización (Desde el Administrador)
    public function store(Request $request)
    {
        $request->validate([
            'trabajo_id' => 'required|exists:trabajos,id',
            'monto' => 'required|numeric',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Valida el archivo
        ]);

        // Lógica para guardar el archivo en Storage de Laravel
        $pathArchivo = null;
        if ($request->hasFile('archivo')) {
            // Se guardará en storage/app/public/cotizaciones
            $pathArchivo = $request->file('archivo')->store('cotizaciones', 'public');
        }

        $cotizacion = Cotizacion::updateOrCreate(
            ['trabajo_id' => $request->trabajo_id],
            [
                'descripcion' => $request->descripcion,
                'monto' => $request->monto,
                'estado' => $request->estado ?? 'Pendiente',
                'archivo' => $pathArchivo // <-- ¡NUEVO!
            ]
        );

        return response()->json([
            'message' => 'Cotización creada/actualizada exitosamente',
            'data' => $cotizacion
        ], 201);
    }

    // ✅❌ 3. Cambiar estado de la cotización (Aprobar o Rechazar desde el Cliente)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:Pendiente,Aprobada,Rechazada'
        ]);

        $cotizacion = Cotizacion::find($id);

        if (!$cotizacion) {
            return response()->json(['message' => 'Cotización no encontrada'], 404);
        }

        $cotizacion->update(['estado' => $request->estado]);

        // Si la cotización fue aprobada, actualizamos el estado del Trabajo
        // y reseteamos el trabajador_id para que el Admin asigne al técnico de reparación.
        if ($request->estado === 'Aprobada') {
            // Aseguramos cargar el modelo de Trabajo
            $trabajo = \App\Models\Trabajo::find($cotizacion->trabajo_id);
            if ($trabajo) {
                $trabajo->estado = 'Cotización Aprobada';
                $trabajo->trabajador_id = null; // Liberar para reasignar
                $trabajo->visitado = false; // Ocultar el aviso de visita de diagnóstico en el Frontend
                $trabajo->save();
            }
        }

        return response()->json([
            'message' => 'El estado ha sido actualizado a ' . $request->estado,
            'data' => $cotizacion
        ]);
    }
    
    // 🔍 4. (Opcional) Obtener todas las cotizaciones de un cliente en específico para su listado
    // Se utilizaría cruzando los trabajos con negocio_id -> user_id, pero se puede añadir luego.
}

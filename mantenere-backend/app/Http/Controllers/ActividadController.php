<?php

namespace App\Http\Controllers; // <-- QUÍTALE EL \Api AQUÍ

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Trabajador;
use Illuminate\Http\Request;

class ActividadController extends Controller
{
    // Guardar lo que el técnico escribió en el Modal + Equipos y Cotización
    public function store(Request $request)
    {
        $request->validate([
            'trabajo_id' => 'required|exists:trabajos,id',
            'tipo' => 'required|string',
            'descripcion' => 'required|string',
            'equipo' => 'nullable|array',
            'cotizacion_sugerida' => 'nullable|array',
            'refacciones' => 'nullable|array'
        ]);

        // Intentar obtener el técnico asociado al usuario autenticado
        $trabajador = Trabajador::where('user_id', auth()->id())->first();

        // 1. Crear actividad base
        $actividadData = $request->only(['trabajo_id', 'tipo', 'descripcion']);
        if ($trabajador) {
            $actividadData['trabajador_id'] = $trabajador->id;
        }

        $actividad = Actividad::create($actividadData);

        // 2. Crear equipo si existe
        if ($request->has('equipo') && !empty($request->equipo)) {
            $actividad->equipo()->create($request->equipo);
        }

        // 3. Crear cotización sugerida si existe
        if ($request->has('cotizacion_sugerida') && !empty($request->cotizacion_sugerida)) {
            $actividad->cotizacion()->create($request->cotizacion_sugerida);
        }

        // 4. Guardar registro de refacciones
        if ($request->has('refacciones') && !empty($request->refacciones)) {
            foreach ($request->refacciones as $refaccion) {
                $actividad->refacciones()->create([
                    'pieza' => $refaccion['pieza'],
                    'cantidad' => $refaccion['cantidad'] ?? 1,
                    'costo_estimado' => $refaccion['costo_estimado'] ?? null,
                    'levantamiento_equipo_id' => $refaccion['levantamiento_equipo_id'] ?? null
                ]);
            }
        }

        return response()->json([
            'message' => 'Registro de actividad guardado y enviado exitosamente.',
            'actividad' => $actividad->load(['equipo', 'cotizacion', 'trabajador', 'refacciones'])
        ], 201);
    }

    // Traer el "Historial" con relaciones cargadas
    public function getByTrabajo($trabajoId)
    {
        $actividades = Actividad::where('trabajo_id', $trabajoId)
            ->with(['equipo', 'cotizacion', 'trabajador', 'refacciones'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($actividades);
    }

    // Eliminar una actividad
    public function destroy($id)
    {
        $actividad = Actividad::find($id);

        if (!$actividad) {
            return response()->json(['message' => 'Actividad no encontrada.'], 404);
        }

        $actividad->delete();

        return response()->json(['message' => 'Actividad eliminada correctamente.'], 200);
    }
}

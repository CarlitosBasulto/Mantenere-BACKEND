<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistEquipo;
use Illuminate\Http\Request;

class ChecklistEquipoController extends Controller
{
    // 🔍 Obtener el checklist guardado de un trabajo específico
    public function showByTrabajo($trabajo_id)
    {
        // Traemos todo el checklist de este trabajo
        $items = ChecklistEquipo::where('trabajo_id', $trabajo_id)->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'No hay checklist registrado para este trabajo'], 404);
        }

        // Agrupamos la respuesta en 'herramienta' y 'seguridad' para facilitarle la vida a React
        return response()->json([
            'herramientas' => $items->where('tipo', 'herramienta')->values(),
            'seguridad' => $items->where('tipo', 'seguridad')->values(),
        ]);
    }

    // ➕ Guardar o Actualizar masivamente el checklist
    public function store(Request $request)
    {
        $request->validate([
            'trabajo_id' => 'required|exists:trabajos,id',
            'herramientas' => 'array',
            'seguridad' => 'array',
        ]);

        $trabajo_id = $request->trabajo_id;

        // 1. Limpiamos el checklist viejo para no duplicar si deciden regresar a editar
        ChecklistEquipo::where('trabajo_id', $trabajo_id)->delete();

        // 2. Insertamos las herramientas guardadas
        if ($request->has('herramientas')) {
            foreach ($request->herramientas as $herr) {
                ChecklistEquipo::create([
                    'trabajo_id' => $trabajo_id,
                    'tipo' => 'herramienta',
                    'nombre' => $herr['name'] ?? $herr['nombre'],
                    'checked' => $herr['checked'] ?? false,
                ]);
            }
        }

        // 3. Insertamos el equipo de seguridad
        if ($request->has('seguridad')) {
            foreach ($request->seguridad as $seg) {
                ChecklistEquipo::create([
                    'trabajo_id' => $trabajo_id,
                    'tipo' => 'seguridad',
                    'nombre' => $seg['name'] ?? $seg['nombre'],
                    'checked' => $seg['checked'] ?? false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Checklist guardado exitosamente.'
        ], 201);
    }
}

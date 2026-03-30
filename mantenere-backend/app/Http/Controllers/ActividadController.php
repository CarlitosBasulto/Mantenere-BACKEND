<?php

namespace App\Http\Controllers; // <-- QUÍTALE EL \Api AQUÍ

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use Illuminate\Http\Request;

class ActividadController extends Controller
{
    // Guardar lo que el técnico escribió en el Modal
    public function store(Request $request)
    {
        $request->validate([
            'trabajo_id' => 'required|exists:trabajos,id',
            'tipo' => 'required|string',
            'descripcion' => 'required|string',
        ]);

        $actividad = Actividad::create($request->all());

        return response()->json([
            'message' => 'Registro de actividad guardado y enviado exitosamente.',
            'actividad' => $actividad
        ], 201);
    }

    // Traer el "Historial" del que hablábamos para que el Admin lo lea
    public function getByTrabajo($trabajoId)
    {
        $actividades = Actividad::where('trabajo_id', $trabajoId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($actividades);
    }
}

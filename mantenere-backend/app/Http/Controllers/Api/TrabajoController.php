<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trabajo;
use Illuminate\Http\Request;

class TrabajoController extends Controller
{
    // 🔍 LISTAR TODOS LOS TRABAJOS (SOLICITUDES)
    // Traemos también los datos del trabajador y negocio asociados gracias a las relaciones en tu modelo
    public function index()
    {
        return response()->json(
            Trabajo::with(['trabajador', 'negocio'])->orderBy('created_at', 'desc')->get()
        );
    }

    // 🔍 VER UN TRABAJO ESPECÍFICO
    public function show($id)
    {
        $trabajo = Trabajo::with(['trabajador', 'negocio', 'reporte'])->find($id);

        if (!$trabajo) {
            return response()->json(['message' => 'Trabajo no encontrado'], 404);
        }

        return response()->json($trabajo);
    }

    // ➕ CREAR NUEVO TRABAJO (SOLICITUD)
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string',
            'descripcion' => 'nullable|string',
            'prioridad' => 'required|in:Alta,Media,Baja',
            'negocio_id' => 'required|exists:negocios,id',
            'fecha_programada' => 'nullable|date',
        ]);

        $trabajo = Trabajo::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'prioridad' => $request->prioridad,
            'estado' => 'Pendiente', // Por defecto inicia pendiente
            'negocio_id' => $request->negocio_id,
            'fecha_programada' => $request->fecha_programada,
            // trabajador_id va vacío al principio hasta que un admin lo asigne
        ]);

        return response()->json($trabajo, 201);
    }

    // 🔄 ASIGNAR UN TRABAJADOR A LA SOLICITUD
    public function asignarTrabajador(Request $request, $id)
    {
        $request->validate([
            'trabajador_id' => 'required|exists:trabajadores,id'
        ]);

        $trabajo = Trabajo::findOrFail($id);
        $trabajo->trabajador_id = $request->trabajador_id;

        // Opcional: Si se asigna alguien, pasarlo a "En proceso"
        if ($trabajo->estado === 'Pendiente') {
            $trabajo->estado = 'En proceso';
        }

        $trabajo->save();

        return response()->json($trabajo);
    }

    // 🔄 CAMBIAR EL ESTADO DEL TRABAJO
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string',
            'visitado' => 'nullable|boolean'
        ]);

        $trabajo = Trabajo::findOrFail($id);
        $trabajo->estado = $request->estado;
        
        if ($request->has('visitado')) {
            $trabajo->visitado = $request->visitado;
        }
        
        $trabajo->save();

        return response()->json($trabajo);
    }
}

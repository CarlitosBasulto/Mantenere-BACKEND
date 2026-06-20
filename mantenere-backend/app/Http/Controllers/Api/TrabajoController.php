<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trabajo;
use Illuminate\Http\Request;

class TrabajoController extends Controller
{
    // 🔍 LISTAR TODOS LOS TRABAJOS (SOLICITUDES)
    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = $user && $user->role ? strtolower($user->role->name) : '';

        $query = Trabajo::with(['trabajador', 'negocio', 'reporte'])->orderBy('created_at', 'desc');

        if ($roleName === 'admin-autonomo') {
            $query->where('admin_autonomo_id', $user->id);
        } elseif ($roleName === 'admin' || $roleName === 'root' || $roleName === 'sub-admin') {
            $query->whereNull('admin_autonomo_id');
        }
        // Técnicos y clientes: los trabajos ya tienen su negocio_id que los delimita

        return response()->json($query->get());
    }

    // 🔍 VER UN TRABAJO ESPECÍFICO
    public function show($id)
    {
        $trabajo = Trabajo::with(['trabajador', 'negocio.user', 'reporte', 'mantenimientoSolicitudVisita.levantamientoEquipo', 'mantenimientoSolicitudReparacion.levantamientoEquipo'])->find($id);

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
            'tipo' => 'nullable|string',
            'negocio_id' => 'required|exists:negocios,id',
            'fecha_programada' => 'nullable|date',
            'foto' => 'nullable|image|max:5120', // Hasta 5MB
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|max:5120',
        ]);

        $fotoUrls = [];
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('trabajos/fotos', 'public');
            $fotoUrls[] = asset('storage/' . $path);
        }

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                $path = $file->store('trabajos/fotos', 'public');
                $fotoUrls[] = asset('storage/' . $path);
            }
        }

        $fotoUrl = null;
        if (count($fotoUrls) === 1) {
            $fotoUrl = $fotoUrls[0];
        } elseif (count($fotoUrls) > 1) {
            $fotoUrl = json_encode($fotoUrls);
        }

        // Detectar si quien crea es Admin Autónomo
        $authUser = $request->user();
        $adminAutonomoId = null;
        if ($authUser && $authUser->role && strtolower($authUser->role->name) === 'admin-autonomo') {
            $adminAutonomoId = $authUser->id;
        } else {
            // Heredar admin_autonomo_id del negocio si aplica (ej. creado por un encargado)
            $negocio = \App\Models\Negocio::find($request->negocio_id);
            if ($negocio && $negocio->admin_autonomo_id) {
                $adminAutonomoId = $negocio->admin_autonomo_id;
            }
        }

        $trabajo = Trabajo::create([
            'titulo'             => $request->titulo,
            'descripcion'        => $request->descripcion,
            'prioridad'          => $request->prioridad,
            'tipo'               => $request->tipo,
            'estado'             => 'Pendiente',
            'negocio_id'         => $request->negocio_id,
            'fecha_programada'   => $request->fecha_programada,
            'foto_url'           => $fotoUrl,
            'admin_autonomo_id'  => $adminAutonomoId,
        ]);

        return response()->json($trabajo, 201);
    }

    // 🔄 ASIGNAR UN TRABAJADOR A LA SOLICITUD
    public function asignarTrabajador(Request $request, $id)
    {
        $request->validate([
            'trabajador_id' => 'nullable|exists:trabajadores,id'
        ]);

        $trabajo = Trabajo::findOrFail($id);
        $trabajo->trabajador_id = $request->trabajador_id;

        // Opcional: Si se asigna alguien, pasarlo a "En proceso"
        if ($request->trabajador_id && $trabajo->estado === 'Pendiente') {
            $trabajo->estado = 'En proceso';
        }

        $trabajo->save();

        return response()->json($trabajo);
    }

    // 🔄 ACTUALIZACIÓN GENERAL DEL TRABAJO
    public function update(Request $request, $id)
    {
        $trabajo = Trabajo::findOrFail($id);
        
        // Validación dinámica de campos que pueden venir en el JSON
        $data = $request->validate([
            'titulo' => 'sometimes|string',
            'descripcion' => 'sometimes|nullable|string',
            'prioridad' => 'sometimes|in:Alta,Media,Baja',
            'estado' => 'sometimes|string',
            'tipo' => 'sometimes|nullable|string',
            'fechaAsignada' => 'sometimes|nullable|date',
            'horaAsignada' => 'sometimes|nullable|string',
            'visitado' => 'sometimes|boolean',
            'trabajador_id' => 'sometimes|nullable|exists:trabajadores,id',
            'fecha_programada' => 'sometimes|nullable|date',
        ]);

        $trabajo->update($data);

        return response()->json([
            'message' => 'Trabajo actualizado con éxito.',
            'trabajo' => $trabajo->load(['trabajador', 'negocio'])
        ]);
    }

    // 🔄 CAMBIAR EL ESTADO DEL TRABAJO

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

    // 🗑️ ELIMINAR UN TRABAJO (SOLICITUD)
    public function destroy($id)
    {
        $trabajo = Trabajo::find($id);

        if (!$trabajo) {
            return response()->json(['message' => 'Trabajo no encontrado'], 404);
        }

        $trabajo->delete();

        return response()->json(['message' => 'Solicitud eliminada exitosamente.'], 200);
    }
}

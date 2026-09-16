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

        $isTechnician = in_array($roleName, ['tecnico-autonomo', 'tecnico-normal', 'tecnico', 'tecnico-proveedor']);

        // Para técnicos, optimizamos las relaciones para no arrastrar cadenas gigantes de base64 en reportes
        $relations = $isTechnician
            ? [
                'trabajador:id,nombre,correo,user_id,telefono',
                'negocio:id,nombre,calle,colonia,ciudad,admin_autonomo_id',
                'reporte:id,trabajo_id,fecha,descripcion'
              ]
            : ['trabajador', 'negocio', 'reporte'];

        $query = Trabajo::with($relations)->orderBy('created_at', 'desc');

        if ($roleName === 'propietario-autonomo' || $roleName === 'administrador-general') {
            $query->where('admin_autonomo_id', $user->admin_autonomo_id ?? $user->id);
        } elseif ($roleName === 'admin' || $roleName === 'root') {
            $query->whereNull('admin_autonomo_id');
        } elseif ($roleName === 'gerente-sucursal') {
            if (isset($user->negocio_id)) {
                $query->where('negocio_id', $user->negocio_id);
            }
        } elseif ($roleName === 'cliente') {
            $negociosIds = \App\Models\Negocio::where('user_id', $user->id)
                ->pluck('id');
            $query->whereIn('negocio_id', $negociosIds);
        } elseif ($isTechnician) {
            $trabajador = $user->trabajador ?? \App\Models\Trabajador::where('user_id', $user->id)->orWhere('correo', $user->email)->first();
            $trabajadorId = $trabajador ? $trabajador->id : null;
            $query->where(function($q) use ($trabajadorId, $user) {
                if ($trabajadorId) {
                    $q->where('trabajador_id', $trabajadorId);
                }
                $q->orWhere('trabajador_id', $user->id);
            });
            if ($roleName === 'tecnico-autonomo' && !empty($user->admin_autonomo_id)) {
                $query->where('admin_autonomo_id', $user->admin_autonomo_id);
            }
        }
        
        // Filtros dinámicos recibidos por query parameters
        if ($request->has('negocio_id')) {
            $query->where('negocio_id', $request->query('negocio_id'));
        }

        if ($request->has('trabajador_id')) {
            $query->where('trabajador_id', $request->query('trabajador_id'));
        }

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
            'trabajador_id' => 'nullable|exists:trabajadores,id'
        ]);

        $fotoUrls = [];
        $isLocal = app()->environment('local');
        $cloudinaryOptions = [
            'folder'    => 'mantenere/trabajos',
            'quality'   => 'auto:low',
            'fetch_format' => 'auto',
        ];

        if ($request->hasFile('foto')) {
            if ($isLocal) {
                $path = $request->file('foto')->store('trabajos/fotos', 'public');
                $fotoUrls[] = asset('storage/' . $path);
            } else {
                $result = cloudinary()->uploadApi()->upload($request->file('foto')->getRealPath(), $cloudinaryOptions);
                $fotoUrls[] = $result['secure_url'];
            }
        }

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                if ($isLocal) {
                    $path = $file->store('trabajos/fotos', 'public');
                    $fotoUrls[] = asset('storage/' . $path);
                } else {
                    $result = cloudinary()->uploadApi()->upload($file->getRealPath(), $cloudinaryOptions);
                    $fotoUrls[] = $result['secure_url'];
                }
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
        if ($authUser && $authUser->role && in_array(strtolower($authUser->role->name), ['propietario-autonomo', 'administrador-general', 'admin-autonomo', 'autonomo'])) {
            $adminAutonomoId = $authUser->admin_autonomo_id ?? $authUser->id;
        } else {
            $negocio = \App\Models\Negocio::with('user.role')->find($request->negocio_id);
            if ($negocio) {
                if ($negocio->admin_autonomo_id) {
                    $adminAutonomoId = $negocio->admin_autonomo_id;
                } elseif ($negocio->user) {
                    $ownerRole = strtolower($negocio->user->role->name ?? '');
                    if (in_array($ownerRole, ['propietario-autonomo', 'administrador-general', 'admin-autonomo', 'autonomo'])) {
                        $adminAutonomoId = $negocio->user->admin_autonomo_id ?? $negocio->user->id;
                    }
                }
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
            'trabajador_id'      => $request->trabajador_id,
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

        if ($request->trabajador_id) {
            $trabajo->motivo_rechazo = null;
            $trabajo->rechazado_por_nombre = null;
            $trabajo->estado = 'Solicitud';
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
            'visitado' => 'nullable|boolean',
            'hora_llegada' => 'nullable|string',
            'latitud_llegada' => 'nullable|string',
            'longitud_llegada' => 'nullable|string',
            'motivo_rechazo' => 'nullable|string',
            'rechazado_por_nombre' => 'nullable|string',
        ]);

        $trabajo = Trabajo::findOrFail($id);
        $trabajo->estado = $request->estado;
        
        if ($request->has('visitado')) {
            $trabajo->visitado = $request->visitado;
        }
        
        if ($request->has('hora_llegada')) {
            $trabajo->hora_llegada = $request->hora_llegada;
        }

        if ($request->has('latitud_llegada')) {
            $trabajo->latitud_llegada = $request->latitud_llegada;
        }

        if ($request->has('longitud_llegada')) {
            $trabajo->longitud_llegada = $request->longitud_llegada;
        }

        if ($request->has('motivo_rechazo')) {
            $trabajo->motivo_rechazo = $request->motivo_rechazo;
        }

        if ($request->has('rechazado_por_nombre')) {
            $trabajo->rechazado_por_nombre = $request->rechazado_por_nombre;
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

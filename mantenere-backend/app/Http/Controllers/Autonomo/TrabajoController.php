<?php

namespace App\Http\Controllers\Autonomo;

use App\Http\Controllers\Controller;
use App\Models\Trabajo;
use Illuminate\Http\Request;

/**
 * TrabajoController — Ecosistema AUTÓNOMO
 * Maneja trabajos del ecosistema autónomo (admin_autonomo_id IS NOT NULL).
 * Roles: propietario-autonomo (4), administrador-general (5), gerente-sucursal (6), tecnico-autonomo (7)
 * Supervisión: root (0), Admin (1) también pueden acceder.
 */
class TrabajoController extends Controller
{
    /**
     * Obtener el admin_autonomo_id efectivo del usuario autenticado.
     */
    private function resolveAdminId($user): ?int
    {
        $roleName = strtolower($user->role->name);

        if ($roleName === 'propietario-autonomo') {
            return $user->id;
        }

        return $user->admin_autonomo_id ?? null;
    }

    public function index(Request $request)
    {
        $user     = $request->user();
        $roleName = strtolower($user->role->name);

        $query = Trabajo::with(['trabajador', 'negocio', 'reporte'])
            ->whereNotNull('admin_autonomo_id')
            ->orderBy('created_at', 'desc');

        // Root y Admin supervisan: ven todos los trabajos autónomos
        if (in_array($roleName, ['root', 'admin'])) {
            // Sin filtro adicional
        } elseif ($roleName === 'gerente-sucursal') {
            if ($user->negocio_id) {
                $query->where('negocio_id', $user->negocio_id);
            }
        } else {
            // propietario-autonomo, administrador-general, tecnico-autonomo
            $adminId = $this->resolveAdminId($user);
            if ($adminId) {
                $query->where('admin_autonomo_id', $adminId);
            }
        }

        if ($request->has('negocio_id'))    $query->where('negocio_id', $request->query('negocio_id'));
        if ($request->has('trabajador_id')) $query->where('trabajador_id', $request->query('trabajador_id'));

        return response()->json($query->get());
    }

    public function show($id)
    {
        $trabajo = Trabajo::with([
            'trabajador', 'negocio.user', 'reporte',
            'mantenimientoSolicitudVisita.levantamientoEquipo',
            'mantenimientoSolicitudReparacion.levantamientoEquipo'
        ])->whereNotNull('admin_autonomo_id')->find($id);

        if (!$trabajo) {
            return response()->json(['message' => 'Trabajo no encontrado'], 404);
        }

        return response()->json($trabajo);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'           => 'required|string',
            'descripcion'      => 'nullable|string',
            'prioridad'        => 'required|in:Alta,Media,Baja',
            'tipo'             => 'nullable|string',
            'negocio_id'       => 'required|exists:negocios,id',
            'fecha_programada' => 'nullable|date',
            'foto'             => 'nullable|image|max:5120',
            'fotos'            => 'nullable|array',
            'fotos.*'          => 'image|max:5120',
            'trabajador_id'    => 'nullable|exists:trabajadores,id',
        ]);

        $authUser = $request->user();
        $adminId  = $this->resolveAdminId($authUser);

        // Si no tiene admin_autonomo_id propio, heredar del negocio
        if (!$adminId) {
            $negocio = \App\Models\Negocio::find($request->negocio_id);
            $adminId = $negocio?->admin_autonomo_id;
        }

        $fotoUrls = [];
        $isLocal  = app()->environment('local');
        $cloudinaryOptions = ['folder' => 'mantenere/trabajos', 'quality' => 'auto:low', 'fetch_format' => 'auto'];

        if ($request->hasFile('foto')) {
            if ($isLocal) {
                $path = $request->file('foto')->store('trabajos/fotos', 'public');
                $fotoUrls[] = asset('storage/' . $path);
            } else {
                $result = cloudinary()->uploadApi()->upload($request->file('foto')->getRealPath(), $cloudinaryOptions);
                $fotoUrls[] = $result['secure_url'];
            }
        }

        $fotoUrl = count($fotoUrls) === 1 ? $fotoUrls[0] : (count($fotoUrls) > 1 ? json_encode($fotoUrls) : null);

        $trabajo = Trabajo::create([
            'titulo'           => $request->titulo,
            'descripcion'      => $request->descripcion,
            'prioridad'        => $request->prioridad,
            'tipo'             => $request->tipo,
            'estado'           => 'Pendiente',
            'negocio_id'       => $request->negocio_id,
            'fecha_programada' => $request->fecha_programada,
            'foto_url'         => $fotoUrl,
            'admin_autonomo_id'=> $adminId,
            'trabajador_id'    => $request->trabajador_id,
        ]);

        return response()->json($trabajo, 201);
    }

    public function asignarTrabajador(Request $request, $id)
    {
        $request->validate(['trabajador_id' => 'nullable|exists:trabajadores,id']);
        $trabajo = Trabajo::whereNotNull('admin_autonomo_id')->findOrFail($id);
        $trabajo->trabajador_id = $request->trabajador_id;
        if ($request->trabajador_id && $trabajo->estado === 'Pendiente') $trabajo->estado = 'En proceso';
        $trabajo->save();
        return response()->json($trabajo);
    }

    public function update(Request $request, $id)
    {
        $trabajo = Trabajo::whereNotNull('admin_autonomo_id')->findOrFail($id);
        $data = $request->validate([
            'titulo'           => 'sometimes|string',
            'descripcion'      => 'sometimes|nullable|string',
            'prioridad'        => 'sometimes|in:Alta,Media,Baja',
            'estado'           => 'sometimes|string',
            'tipo'             => 'sometimes|nullable|string',
            'visitado'         => 'sometimes|boolean',
            'trabajador_id'    => 'sometimes|nullable|exists:trabajadores,id',
            'fecha_programada' => 'sometimes|nullable|date',
        ]);
        $trabajo->update($data);
        return response()->json(['message' => 'Trabajo actualizado.', 'trabajo' => $trabajo->load(['trabajador', 'negocio'])]);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $request->validate(['estado' => 'required|string', 'visitado' => 'nullable|boolean', 'hora_llegada' => 'nullable|string', 'latitud_llegada' => 'nullable|string', 'longitud_llegada' => 'nullable|string']);
        $trabajo = Trabajo::whereNotNull('admin_autonomo_id')->findOrFail($id);
        $trabajo->estado = $request->estado;
        if ($request->has('visitado'))         $trabajo->visitado         = $request->visitado;
        if ($request->has('hora_llegada'))     $trabajo->hora_llegada     = $request->hora_llegada;
        if ($request->has('latitud_llegada'))  $trabajo->latitud_llegada  = $request->latitud_llegada;
        if ($request->has('longitud_llegada')) $trabajo->longitud_llegada = $request->longitud_llegada;
        $trabajo->save();
        return response()->json($trabajo);
    }

    public function destroy($id)
    {
        $trabajo = Trabajo::whereNotNull('admin_autonomo_id')->find($id);
        if (!$trabajo) return response()->json(['message' => 'Trabajo no encontrado'], 404);
        $trabajo->delete();
        return response()->json(['message' => 'Solicitud eliminada.'], 200);
    }
}

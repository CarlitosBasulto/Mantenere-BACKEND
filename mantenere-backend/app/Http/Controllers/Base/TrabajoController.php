<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Models\Trabajo;
use Illuminate\Http\Request;

/**
 * TrabajoController — Ecosistema BASE
 * Maneja trabajos solo del sistema principal (admin_autonomo_id IS NULL).
 * Roles: root (0), Admin (1), Cliente (2), tecnico-normal (3)
 */
class TrabajoController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->user();
        $roleName = strtolower($user->role->name);

        $query = Trabajo::with(['trabajador', 'negocio', 'reporte'])
            ->whereNull('admin_autonomo_id')
            ->orderBy('created_at', 'desc');

        // Cliente solo ve trabajos de sus negocios
        if ($roleName === 'cliente') {
            $negociosIds = \App\Models\Negocio::where('user_id', $user->id)
                ->orWhere('encargado', $user->name)
                ->pluck('id');
            $query->whereIn('negocio_id', $negociosIds);
        }

        // Filtros dinámicos
        if ($request->has('negocio_id')) {
            $query->where('negocio_id', $request->query('negocio_id'));
        }
        if ($request->has('trabajador_id')) {
            $query->where('trabajador_id', $request->query('trabajador_id'));
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $trabajo = Trabajo::with([
            'trabajador', 'negocio.user', 'reporte',
            'mantenimientoSolicitudVisita.levantamientoEquipo',
            'mantenimientoSolicitudReparacion.levantamientoEquipo'
        ])->whereNull('admin_autonomo_id')->find($id);

        if (!$trabajo) {
            return response()->json(['message' => 'Trabajo no encontrado'], 404);
        }

        return response()->json($trabajo);
    }

    public function store(Request $request)
    {
        $request->validate([
            'estado'            => 'required|string',
            'visitado'          => 'nullable|boolean',
            'hora_llegada'      => 'nullable|string',
            'latitud_llegada'   => 'nullable|string',
            'longitud_llegada'  => 'nullable|string',
            'motivo_rechazo'       => 'nullable|string',
            'rechazado_por_nombre' => 'nullable|string',
        ]);
        $trabajo = Trabajo::whereNull('admin_autonomo_id')->findOrFail($id);
        $trabajo->estado = $request->estado;
        if ($request->has('visitado'))         $trabajo->visitado         = $request->visitado;
        if ($request->has('hora_llegada'))     $trabajo->hora_llegada     = $request->hora_llegada;
        if ($request->has('latitud_llegada'))  $trabajo->latitud_llegada  = $request->latitud_llegada;
        if ($request->has('longitud_llegada')) $trabajo->longitud_llegada = $request->longitud_llegada;
        if ($request->has('motivo_rechazo'))       $trabajo->motivo_rechazo       = $request->motivo_rechazo;
        if ($request->has('rechazado_por_nombre')) $trabajo->rechazado_por_nombre = $request->rechazado_por_nombre;

        $trabajo->save();
        return response()->json($trabajo);
    }

    public function destroy($id)
    {
        $trabajo = Trabajo::whereNull('admin_autonomo_id')->find($id);
        if (!$trabajo) {
            return response()->json(['message' => 'Trabajo no encontrado'], 404);
        }
        $trabajo->delete();
        return response()->json(['message' => 'Solicitud eliminada.'], 200);
    }
}


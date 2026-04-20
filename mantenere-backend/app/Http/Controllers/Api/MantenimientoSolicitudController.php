<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MantenimientoSolicitud;
use App\Models\Trabajo;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MantenimientoSolicitudController extends Controller
{
    // GET /api/mantenimiento-solicitudes (Para el Admin o para listar)
    public function index(Request $request)
    {
        $query = MantenimientoSolicitud::with([
            'cliente', 
            'negocio', 
            'levantamientoEquipo', 
            'visitaTrabajo.reporte', 
            'reparacionTrabajo.reporte'
        ]);

        if ($request->has('negocio_id')) {
            $query->where('negocio_id', $request->negocio_id);
        }

        $solicitudes = $query->orderBy('created_at', 'desc')->get();
        return response()->json($solicitudes);
    }

    // POST /api/mantenimiento-solicitudes (Cliente reporta un problema)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:users,id',
            'negocio_id' => 'required|exists:negocios,id',
            'levantamiento_equipo_id' => 'required|exists:levantamiento_equipos,id',
            'descripcion_problema' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $solicitud = MantenimientoSolicitud::create([
            'cliente_id' => $request->cliente_id,
            'negocio_id' => $request->negocio_id,
            'levantamiento_equipo_id' => $request->levantamiento_equipo_id,
            'descripcion_problema' => $request->descripcion_problema,
            'estado' => 'Pendiente',
        ]);

        return response()->json(['message' => 'Problema reportado exitosamente', 'data' => $solicitud], 201);
    }

    // GET /api/mantenimiento-solicitudes/{id} (Ver detalle)
    public function show($id)
    {
        $solicitud = MantenimientoSolicitud::with(['cliente', 'negocio', 'levantamientoEquipo', 'visitaTrabajo', 'reparacionTrabajo', 'visitas.tecnico', 'reportes.tecnico'])->find($id);

        if (!$solicitud) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        return response()->json($solicitud);
    }

    // POST /api/mantenimiento-solicitudes/{id}/asignar-visita
    public function asignarVisita($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tecnico_id' => 'required|exists:users,id',
            'fecha_programada' => 'required|date',
            'hora_programada' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $solicitud = MantenimientoSolicitud::with('levantamientoEquipo')->find($id);

        if (!$solicitud) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        if ($solicitud->estado !== 'Pendiente') {
            return response()->json(['message' => 'La solicitud no está en estado Pendiente'], 400);
        }

        $trabajador = \App\Models\Trabajador::where('user_id', $request->tecnico_id)->first();
        if (!$trabajador) {
            return response()->json(['message' => 'El técnico asignado no existe como trabajador.'], 400);
        }

        // Crear el Trabajo de Visita
        $trabajo = Trabajo::create([
            'titulo' => 'Mantenimiento (Visita): ' . ($solicitud->levantamientoEquipo->nombre ?? 'Equipo'),
            'descripcion' => "Revisión y diagnóstico.\nProblema reportado: " . $solicitud->descripcion_problema,
            'fecha_programada' => $request->fecha_programada,
            'hora_programada' => $request->hora_programada,
            'trabajador_id' => $trabajador->id,
            'negocio_id' => $solicitud->negocio_id,
            'estado' => 'Asignado',
            'user_id' => $request->tecnico_id,
            'prioridad' => 'Media',
            'tipo' => 'Visita',
            'visitado' => false,
        ]);

        // Actualizar solicitud
        $solicitud->estado = 'Visita Asignada';
        $solicitud->visita_trabajo_id = $trabajo->id;
        $solicitud->save();

        // Notificar al Técnico
        Notificacion::create([
            'user_id' => $request->tecnico_id,
            'titulo' => 'Nueva Visita de Mantenimiento',
            'mensaje' => 'Se te ha asignado una visita para el equipo: ' . ($solicitud->levantamientoEquipo->nombre ?? 'Equipo'),
            'tipo' => 'mantenimiento',
            'enlace' => '/tecnico/trabajo-detalle/' . $trabajo->id,
            'leido' => false,
        ]);

        return response()->json([
            'message' => 'Visita asignada y notificada correctamente',
            'trabajo' => $trabajo
        ]);
    }

    // POST /api/mantenimiento-solicitudes/{id}/asignar-reparacion
    public function asignarReparacion($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tecnico_id' => 'required|exists:users,id',
            'fecha_programada' => 'required|date',
            'hora_programada' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $solicitud = MantenimientoSolicitud::with('levantamientoEquipo')->find($id);

        if (!$solicitud) {
            return response()->json(['message' => 'Solicitud no encontrada'], 404);
        }

        if ($solicitud->estado !== 'Cotización Aceptada') {
            return response()->json(['message' => 'La solicitud no tiene una cotización aceptada.'], 400);
        }

        if ($solicitud->reparacion_trabajo_id !== null) {
            return response()->json(['message' => 'Ya se ha asignado un trabajo de reparación para esta solicitud.'], 400);
        }

        $trabajador = \App\Models\Trabajador::where('user_id', $request->tecnico_id)->first();
        if (!$trabajador) {
            return response()->json(['message' => 'El técnico asignado no existe como trabajador.'], 400);
        }

        // Crear el Trabajo de Reparación
        $trabajo = Trabajo::create([
            'titulo' => 'Mantenimiento (Reparación): ' . ($solicitud->levantamientoEquipo->nombre ?? 'Equipo'),
            'descripcion' => "Reparación tras cotización aprobada.\nProblema reportado: " . $solicitud->descripcion_problema,
            'fecha_programada' => $request->fecha_programada,
            'hora_programada' => $request->hora_programada,
            'trabajador_id' => $trabajador->id,
            'negocio_id' => $solicitud->negocio_id,
            'estado' => 'Asignado',
            'user_id' => $request->tecnico_id,
            'prioridad' => 'Alta', // Alta hace que en el frontend actúe como SOS (Alerta)
            'tipo' => 'Trabajo',
            'visitado' => false,
        ]);

        // Actualizar solicitud con el ESTADO ENUM CORRECTO: "Trabajo Asignado"
        $solicitud->estado = 'Trabajo Asignado';
        $solicitud->reparacion_trabajo_id = $trabajo->id;
        $solicitud->save();

        // Notificar al Técnico
        Notificacion::create([
            'user_id' => $request->tecnico_id,
            'titulo' => 'Nuevo Trabajo de Reparación',
            'mensaje' => 'Se te ha asignado un trabajo de mantenimiento para el equipo: ' . ($solicitud->levantamientoEquipo->nombre ?? 'Equipo'),
            'tipo' => 'Nuevo Trabajo',
            'referencia_id' => $trabajo->id,
            'referencia_tipo' => 'trabajo',
        ]);

        return response()->json([
            'message' => 'Trabajo de reparación asignado y notificado correctamente',
            'trabajo' => $trabajo
        ]);
    }
}

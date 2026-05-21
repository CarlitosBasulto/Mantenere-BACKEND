<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaEquipo;
use Illuminate\Http\Request;

class CategoriaEquipoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaEquipo::all();
        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_equipos,nombre',
        ]);

        $categoria = CategoriaEquipo::create([
            'nombre' => strtoupper($request->nombre),
        ]);

        return response()->json([
            'message' => 'Categoría creada con éxito',
            'data' => $categoria
        ], 201);
    }

    public function destroy($id)
    {
        $categoria = CategoriaEquipo::find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        $categoria->delete();

        return response()->json([
            'message' => 'Categoría definitiva eliminada con éxito'
        ]);
    }

    public function consumoReporte()
    {
        $consumos = \App\Models\EquipoHistorialRefaccion::with([
            'equipo.categoria',
            'equipo.area.negocio',
            'actividad.trabajador',
            'actividad.trabajo.reporte',
            'actividad.trabajo.mantenimientoSolicitudVisita',
            'actividad.trabajo.mantenimientoSolicitudReparacion'
        ])->get();

        return response()->json($consumos);
    }

    // ➕ Registrar consumo de refacción manualmente (Admin desde Inventario)
    public function addConsumoManual(Request $request)
    {
        $request->validate([
            'equipo_id'   => 'required|exists:levantamiento_equipos,id',
            'pieza'       => 'required|string|max:255',
            'cantidad'    => 'required|integer|min:1',
            'costo_estimado' => 'nullable|numeric|min:0',
            'categoria_id' => 'nullable|exists:categorias_equipos,id',
        ]);

        $consumo = \App\Models\EquipoHistorialRefaccion::create([
            'equipo_id'       => $request->equipo_id,
            'pieza'           => strtoupper($request->pieza),
            'cantidad'        => $request->cantidad,
            'costo_estimado'  => $request->costo_estimado ?? null,
            'categoria_id'    => $request->categoria_id ?? null,
            'actividad_id'    => null, // Registro manual del admin
        ]);

        $consumo->load(['equipo.categoria', 'equipo.area.negocio']);

        return response()->json([
            'message' => 'Consumo registrado correctamente',
            'data'    => $consumo,
        ], 201);
    }

    public function updateConsumoCategoria(Request $request, $id)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias_equipos,id',
        ]);

        $consumo = \App\Models\EquipoHistorialRefaccion::findOrFail($id);
        $consumo->update(['categoria_id' => $request->categoria_id]);

        $consumo->load(['equipo.categoria', 'equipo.area.negocio', 'categoria']);

        return response()->json([
            'message' => 'Categoría asignada correctamente a la pieza',
            'data'    => $consumo,
        ], 200);
    }
}

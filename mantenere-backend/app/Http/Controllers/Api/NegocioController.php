<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use Illuminate\Http\Request;

class NegocioController extends Controller
{
    // 🔍 Obtener todos los negocios (Para ListaNegocios del Admin)
    public function index()
    {
        $negocios = Negocio::all();
        return response()->json($negocios);
    }

    // 🔍 Obtener un solo negocio (Para Editar PerfilEmpresa)
    public function show($id)
    {
        $negocio = Negocio::find($id);

        if (!$negocio) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        return response()->json($negocio);
    }

    // ➕ Registrar un nuevo negocio (Para PerfilEmpresa POST)
    public function store(Request $request)
    {
        // Validación de datos básicos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:FC,FS,MALL,W/M',
        ]);

        // Crear el registro masivo
        $negocio = Negocio::create($request->all());

        return response()->json([
            'message' => 'Negocio creado exitosamente',
            'data' => $negocio
        ], 201);
    }

    // ✏️ Actualizar un negocio existente
    public function update(Request $request, $id)
    {
        $negocio = Negocio::find($id);

        if (!$negocio) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $negocio->update($request->all());

        return response()->json([
            'message' => 'Negocio actualizado correctamente',
            'data' => $negocio
        ]);
    }
}

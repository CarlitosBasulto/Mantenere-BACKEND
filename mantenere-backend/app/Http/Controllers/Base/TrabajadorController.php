<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * TrabajadorController — Ecosistema BASE
 * Maneja técnicos del sistema principal (rol tecnico-normal, admin_autonomo_id IS NULL).
 * Roles con acceso: root (0), Admin (1)
 */
class TrabajadorController extends Controller
{
    public function index(Request $request)
    {
        // Admin base solo ve técnicos sin creador_id autónomo (creados por el sistema base)
        $query = Trabajador::with('user')->whereNull('creador_id');

        return response()->json($query->get());
    }

    public function show($id)
    {
        $trabajador = Trabajador::with('user')
            ->withCount(['trabajos' => fn($q) => $q->where('estado', 'Finalizado')])
            ->whereNull('creador_id')
            ->find($id);

        if (!$trabajador) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($trabajador);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'           => 'required|string',
            'correo'           => 'required|email|unique:users,email',
            'password'         => 'required|min:6',
            'puesto'           => 'required|string',
            'telefono'         => 'nullable|string',
            'fecha_nacimiento' => 'nullable|date',
            'direccion'        => 'nullable|string',
            'rfc'              => 'nullable|string',
        ]);

        // En el ecosistema base siempre se asigna rol tecnico-normal
        $roleTecnico = Role::where('name', 'tecnico-normal')->first();
        if (!$roleTecnico) {
            return response()->json(['message' => 'Rol tecnico-normal no existe en el sistema'], 500);
        }

        $user = User::create([
            'name'                 => $request->nombre,
            'email'                => $request->correo,
            'password'             => Hash::make($request->password),
            'role_id'              => $roleTecnico->id,
            'active'               => 1,
            'must_change_password' => true,
        ]);

        $trabajador = Trabajador::create([
            'nombre'           => $request->nombre,
            'correo'           => $request->correo,
            'telefono'         => $request->telefono,
            'puesto'           => $request->puesto,
            'estado'           => 'Activo',
            'user_id'          => $user->id,
            'admin_autonomo_id'=> null, // Sin ecosistema autónomo
            'creador_id'       => null, // Base: sin creador específico
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'direccion'        => $request->direccion,
            'rfc'              => $request->rfc,
        ]);

        return response()->json($trabajador, 201);
    }

    public function toggleEstado($id)
    {
        $trabajador = Trabajador::whereNull('creador_id')->findOrFail($id);

        if (strtolower($trabajador->estado) === 'activo') {
            $trabajador->estado = 'Baja';
            if ($trabajador->user) { $trabajador->user->active = 0; $trabajador->user->save(); }
        } else {
            $trabajador->estado = 'Activo';
            if ($trabajador->user) { $trabajador->user->active = 1; $trabajador->user->save(); }
        }

        $trabajador->save();
        return response()->json($trabajador);
    }

    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::whereNull('creador_id')->find($id);
        if (!$trabajador) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'nombre'           => 'sometimes|string',
            'correo'           => 'sometimes|email|unique:users,email,' . ($trabajador->user_id ?? 0),
            'telefono'         => 'nullable|string',
            'avatar'           => 'nullable|string',
            'puesto'           => 'sometimes|string',
            'fecha_nacimiento' => 'nullable|date',
            'direccion'        => 'nullable|string',
            'rfc'              => 'nullable|string',
        ]);

        $trabajador->update($request->except(['admin_autonomo_id', 'creador_id']));

        if ($trabajador->user) {
            if ($request->has('nombre')) $trabajador->user->name  = $request->nombre;
            if ($request->has('correo')) $trabajador->user->email = $request->correo;
            if ($request->has('avatar')) $trabajador->user->avatar = $request->avatar;
            $trabajador->user->save();
        }

        return response()->json(['message' => 'Perfil actualizado', 'data' => $trabajador]);
    }
}

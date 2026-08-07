<?php

namespace App\Http\Controllers\Autonomo;

use App\Http\Controllers\Controller;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * TrabajadorController — Ecosistema AUTÓNOMO
 * Maneja técnicos del ecosistema autónomo (rol tecnico-autonomo, admin_autonomo_id IS NOT NULL).
 * Roles: propietario-autonomo (4), administrador-general (5), gerente-sucursal (6)
 */
class TrabajadorController extends Controller
{
    private function resolveAdminId($user): ?int
    {
        return strtolower($user->role->name) === 'propietario-autonomo'
            ? $user->id
            : ($user->admin_autonomo_id ?? null);
    }

    public function index(Request $request)
    {
        $user     = $request->user();
        $roleName = strtolower($user->role->name);

        $query = Trabajador::with('user')->whereNotNull('admin_autonomo_id');

        if (in_array($roleName, ['root', 'admin'])) {
            // Sin filtro: supervisa todo
        } elseif ($roleName === 'gerente-sucursal') {
            // Gerente ve técnicos de su ecosistema
            $adminId = $this->resolveAdminId($user);
            if ($adminId) $query->where('admin_autonomo_id', $adminId);
        } else {
            $adminId = $this->resolveAdminId($user);
            if ($adminId) $query->where('admin_autonomo_id', $adminId);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $trabajador = Trabajador::with('user')
            ->withCount(['trabajos' => fn($q) => $q->where('estado', 'Finalizado')])
            ->whereNotNull('admin_autonomo_id')
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

        // En el ecosistema autónomo siempre se asigna rol tecnico-autonomo
        $roleTecnico = Role::where('name', 'tecnico-autonomo')->first();
        if (!$roleTecnico) {
            return response()->json(['message' => 'Rol tecnico-autonomo no existe en el sistema'], 500);
        }

        $authUser = $request->user();
        $adminId  = $this->resolveAdminId($authUser);

        $user = User::create([
            'name'                 => $request->nombre,
            'email'                => $request->correo,
            'password'             => Hash::make($request->password),
            'role_id'              => $roleTecnico->id,
            'active'               => 1,
            'must_change_password' => true,
            'admin_autonomo_id'    => $adminId,
        ]);

        $trabajador = Trabajador::create([
            'nombre'            => $request->nombre,
            'correo'            => $request->correo,
            'telefono'          => $request->telefono,
            'puesto'            => $request->puesto,
            'estado'            => 'Activo',
            'user_id'           => $user->id,
            'admin_autonomo_id' => $adminId,
            'creador_id'        => $adminId, // Quien lo crea en el ecosistema autónomo
            'fecha_nacimiento'  => $request->fecha_nacimiento,
            'direccion'         => $request->direccion,
            'rfc'               => $request->rfc,
        ]);

        return response()->json($trabajador, 201);
    }

    public function toggleEstado($id)
    {
        $trabajador = Trabajador::whereNotNull('admin_autonomo_id')->findOrFail($id);

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
        $trabajador = Trabajador::whereNotNull('admin_autonomo_id')->find($id);
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

        $trabajador->update($request->except(['creador_id']));

        if ($trabajador->user) {
            if ($request->has('nombre')) $trabajador->user->name   = $request->nombre;
            if ($request->has('correo')) $trabajador->user->email  = $request->correo;
            if ($request->has('avatar')) $trabajador->user->avatar = $request->avatar;
            if ($request->has('admin_autonomo_id')) $trabajador->user->admin_autonomo_id = $request->admin_autonomo_id;
            $trabajador->user->save();
        }

        return response()->json(['message' => 'Perfil actualizado', 'data' => $trabajador]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrabajadorController extends Controller
{
    // 🔍 LISTAR trabajadores
    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = $user && $user->role ? strtolower($user->role->name) : '';

        $query = Trabajador::with('user');

        if ($roleName === 'admin-autonomo' || $roleName === 'gerente-general') {
            $query->where('admin_autonomo_id', $user->admin_autonomo_id ?? $user->id);
        } elseif ($roleName === 'encargado') {
            $encargadoAdminId = $user->admin_autonomo_id;
            if (!$encargadoAdminId && $user->negocio_id) {
                $negocio = \App\Models\Negocio::find($user->negocio_id);
                if ($negocio) {
                    $encargadoAdminId = $negocio->admin_autonomo_id;
                }
            }
            $query->where('admin_autonomo_id', $encargadoAdminId);
        } elseif ($roleName === 'admin' || $roleName === 'root' || $roleName === 'sub-admin') {
            // Admin principal ve solo técnicos del sistema principal (sin admin_autonomo_id)
            $query->whereNull('admin_autonomo_id');
        }

        return response()->json($query->get());
    }

    // 🔍 VER UNO
    public function show($id)
    {
        $trabajador = Trabajador::with('user')
            ->withCount(['trabajos' => function ($query) {
                $query->where('estado', 'Finalizado');
            }])
            ->find($id);

        if (!$trabajador) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($trabajador);
    }

    // ➕ CREAR trabajador + usuario
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string',
            'correo'   => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'puesto'   => 'required|string',
            'telefono' => 'nullable|string',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            'rfc' => 'nullable|string'
        ]);

        // 🔥 Obtener rol trabajador dinámicamente
        $roleTrabajador = Role::where('name', 'Trabajador')->first();

        // 1️⃣ Crear usuario
        $user = User::create([
            'name'     => $request->nombre,
            'email'    => $request->correo,
            'password' => Hash::make($request->password),
            'role_id'  => $roleTrabajador->id,
            'active'   => 1
        ]);

        // Determinar admin_autonomo_id
        $authUser = $request->user();
        $adminAutonomoId = null;
        if ($authUser && $authUser->role && (strtolower($authUser->role->name) === 'admin-autonomo' || strtolower($authUser->role->name) === 'gerente-general')) {
            $adminAutonomoId = $authUser->admin_autonomo_id ?? $authUser->id;
        }

        // 2️⃣ Crear trabajador
        $trabajador = Trabajador::create([
            'nombre'           => $request->nombre,
            'correo'           => $request->correo,
            'telefono'         => $request->telefono,
            'puesto'           => $request->puesto,
            'estado'           => 'Activo',
            'user_id'          => $user->id,
            'admin_autonomo_id' => $adminAutonomoId,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'direccion'        => $request->direccion,
            'rfc'              => $request->rfc,
        ]);

        return response()->json($trabajador, 201);
    }

    // 🔄 CAMBIAR ESTADO
    public function toggleEstado($id)
    {
        $trabajador = Trabajador::findOrFail($id);

        // Comparamos sin problemas de mayúsculas
        if (strtolower($trabajador->estado) === 'activo') {
            $trabajador->estado = 'Baja';

            // ESTO DESACTIVARÁ SU INICIO DE SESIÓN EN LA TABLA USERS:
            if ($trabajador->user) {
                $trabajador->user->active = 0;
                $trabajador->user->save();
            }
        }
        else {
            $trabajador->estado = 'Activo';

            // ESTO VOLVERÁ A ACTIVAR SU INICIO DE SESIÓN:
            if ($trabajador->user) {
                $trabajador->user->active = 1;
                $trabajador->user->save();
            }
        }

        $trabajador->save();

        return response()->json($trabajador);
    }

    // 🔄 ACTUALIZAR datos del trabajador (Mi Perfil)
    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string',
            'correo' => 'sometimes|email|unique:users,email,' . ($trabajador->user_id ?? 0),
            'telefono' => 'nullable|string',
            'avatar' => 'nullable|string',
            'puesto' => 'sometimes|string',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            'rfc' => 'nullable|string'
        ]);

        $trabajador->update($request->all());

        // Si el trabajador tiene un usuario vinculado, sincronizar datos básicos
        if ($trabajador->user) {
            if ($request->has('nombre')) $trabajador->user->name = $request->nombre;
            if ($request->has('correo')) $trabajador->user->email = $request->correo;
            if ($request->has('avatar')) $trabajador->user->avatar = $request->avatar;
            $trabajador->user->save();
        }

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'data' => $trabajador
        ]);
    }


}

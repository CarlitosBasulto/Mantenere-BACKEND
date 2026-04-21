<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listado de usuarios según jerarquía
     */
    public function index(Request $request)
    {
        $authUser = $request->user();

        $users = User::with(['role', 'trabajador'])
            ->where('id', '!=', $authUser->id)
            ->whereHas('role', function ($query) use ($authUser) {
                $query->where('hierarchy_level', '>=', $authUser->role->hierarchy_level);
            })
            ->get()
            ->map(function ($user) {
                // Si el usuario no tiene avatar directo, lo toma del trabajador si existe
                if (!$user->avatar && $user->trabajador) {
                    $user->avatar = $user->trabajador->avatar;
                }
                return $user;
            });

        return response()->json($users);
    }

    /**
     * Mostrar usuario específico
     */
    public function show(Request $request, User $user)
    {
        return response()->json($user->load('role'));
    }

    /**
     * Crear usuario con control jerárquico
     */
    public function store(Request $request)
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $targetRole = Role::find($request->role_id);

        if (!$targetRole) {
            return response()->json(['message' => 'Rol inválido'], 422);
        }

        // 🔥 Regla jerárquica
        if ($targetRole->hierarchy_level <= $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes crear un usuario con igual o mayor jerarquía'
            ], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'active' => 1
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 🔥 No puedes modificar a alguien superior
        if ($user->role->hierarchy_level < $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes modificar un usuario con mayor jerarquía'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6',
            'role_id' => 'sometimes|exists:roles,id',
            'active' => 'sometimes|boolean',
            'telefono' => 'nullable|string',
            'rfc' => 'nullable|string',
            'razon_social' => 'nullable|string',
            'direccion_fiscal' => 'nullable|string',
        ]);

        // 🔥 Si intenta cambiar el rol
        if ($request->has('role_id')) {

            $newRole = \App\Models\Role::find($request->role_id);

            if (!$newRole) {
                return response()->json(['message' => 'Rol inválido'], 422);
            }

            // No puedes asignar rol igual o superior al tuyo
            if ($newRole->hierarchy_level <= $authUser->role->hierarchy_level) {
                return response()->json([
                    'message' => 'No puedes asignar un rol igual o superior al tuyo'
                ], 403);
            }

            // No puedes cambiar tu propio rol
            if ($authUser->id === $user->id) {
                return response()->json([
                    'message' => 'No puedes cambiar tu propio rol'
                ], 403);
            }
        }

        // Actualizaciones permitidas
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        if ($request->has('role_id')) {
            $user->role_id = $request->role_id;
        }

        if ($request->has('active')) {
            $user->active = $request->active;
        }

        if ($request->has('telefono')) {
            $user->telefono = $request->telefono;
        }

        if ($request->has('rfc')) {
            $user->rfc = $request->rfc;
        }

        if ($request->has('razon_social')) {
            $user->razon_social = $request->razon_social;
        }

        if ($request->has('direccion_fiscal')) {
            $user->direccion_fiscal = $request->direccion_fiscal;
        }

        $user->save();

        return response()->json($user->load('role'));
    }

    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 🔥 No puedes eliminarte a ti mismo
        if ($authUser->id === $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        // 🔥 No puedes eliminar alguien superior
        if ($user->role->hierarchy_level < $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes eliminar un usuario con mayor jerarquía'
            ], 403);
        }

        // 🔥 Eliminación lógica
        $user->active = 0;
        $user->save();

        return response()->json([
            'message' => 'Usuario desactivado correctamente'
        ]);
    }
}

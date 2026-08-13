<?php

namespace App\Http\Controllers\Autonomo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Listado de usuarios del ecosistema autónomo
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        $myLevel  = $authUser->role->hierarchy_level;

        // Validar que el usuario que consulta pertenece al ecosistema autónomo
        if ($myLevel < 4) {
            return response()->json(['message' => 'No autorizado para ver usuarios autónomos'], 403);
        }

        $usersQuery = User::with(['role', 'trabajador'])
            ->where('id', '!=', $authUser->id)
            ->whereHas('role', function ($query) use ($myLevel) {
                // Solo pueden ver niveles estrictamente inferiores (>) o iguales si es necesario, 
                // pero por regla general, gerentes y admins ven hacia abajo.
                $query->where('hierarchy_level', '>=', $myLevel)
                      ->where('hierarchy_level', '>=', 4); // Asegurarse de que no vean roles base
            });

        $ecosystemId = $authUser->role->name === 'propietario-autonomo' 
            ? $authUser->id 
            : $authUser->admin_autonomo_id;

        $usersQuery->where(function ($q) use ($ecosystemId) {
            // Usuarios que son dueños de sus negocios
            $q->whereHas('negocios', function ($q2) use ($ecosystemId) {
                $q2->where('admin_autonomo_id', $ecosystemId);
            })
            // Usuarios que son encargados de sus negocios
            ->orWhereHas('negocioEncargado', function ($q4) use ($ecosystemId) {
                $q4->where('admin_autonomo_id', $ecosystemId);
            })
            // Usuarios que son sus trabajadores
            ->orWhereHas('trabajador', function ($q3) use ($ecosystemId) {
                $q3->where('admin_autonomo_id', $ecosystemId);
            })
            // Usuarios vinculados directamente
            ->orWhere('admin_autonomo_id', $ecosystemId);
        });

        $users = $usersQuery->get()
            ->map(function ($user) {
                if (!$user->avatar && $user->trabajador) {
                    $user->avatar = $user->trabajador->avatar;
                }
                return $user;
            });

        return response()->json($users);
    }

    public function show(Request $request, User $user)
    {
        return response()->json($user->load('role'));
    }

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
            'rfc' => 'nullable|string',
            'razon_social' => 'nullable|string',
        ]);

        $targetRole = Role::find($request->role_id);

        if (!$targetRole || $targetRole->hierarchy_level < 4) {
            return response()->json(['message' => 'Rol inválido para el ecosistema autónomo'], 422);
        }

        if ($targetRole->hierarchy_level <= $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes crear un usuario con igual o mayor jerarquía'
            ], 403);
        }

        $ecosystemId = $authUser->role->name === 'propietario-autonomo' 
            ? $authUser->id 
            : $authUser->admin_autonomo_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'rfc' => $request->rfc,
            'razon_social' => $request->razon_social,
            'admin_autonomo_id' => $ecosystemId,
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

        if ($user->role->hierarchy_level < $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes modificar un usuario con mayor jerarquía'
            ], 403);
        }

        if ($authUser->role->name === 'administrador-general' && $user->role->name === 'propietario-autonomo') {
            return response()->json([
                'message' => 'No tienes permisos para modificar al Admin Autónomo'
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
            'avatar' => 'nullable|string',
        ]);

        if ($request->has('role_id')) {
            $newRole = \App\Models\Role::find($request->role_id);
            if (!$newRole || $newRole->hierarchy_level < 4) {
                return response()->json(['message' => 'Rol inválido para el ecosistema autónomo'], 422);
            }
            if ($newRole->hierarchy_level <= $authUser->role->hierarchy_level) {
                return response()->json([
                    'message' => 'No puedes asignar un rol igual o superior al tuyo'
                ], 403);
            }
            if ($authUser->id === $user->id) {
                return response()->json([
                    'message' => 'No puedes cambiar tu propio rol'
                ], 403);
            }
            $user->role_id = $request->role_id;
        }

        $user->fill($request->only([
            'name', 'email', 'active', 'telefono', 'rfc', 'razon_social', 'direccion_fiscal', 'avatar', 'cv_url'
        ]));

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
            $user->must_change_password = true;
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

        if ($authUser->id === $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        if ($user->role->hierarchy_level < $authUser->role->hierarchy_level) {
            return response()->json([
                'message' => 'No puedes eliminar un usuario con mayor jerarquía'
            ], 403);
        }

        if ($authUser->role->name === 'administrador-general' && $user->role->name === 'propietario-autonomo') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar al Admin Autónomo'
            ], 403);
        }

        $user->active = 0;
        $user->save();

        return response()->json([
            'message' => 'Usuario desactivado correctamente'
        ]);
    }
}

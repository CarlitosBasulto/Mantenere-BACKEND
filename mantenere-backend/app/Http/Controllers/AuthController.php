<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with('role')
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Normalizar el nombre del rol para el frontend:
        // Sub-Admin se trata como "admin" para que vea el mismo menú.
        // La diferencia de permisos se controla en el backend por hierarchy_level.
        $roleName = strtolower($user->role->name);
        if ($roleName === 'sub-admin') {
            $roleName = 'admin';
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $roleName,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada'
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $clienteRole = \App\Models\Role::where('name', 'cliente')->first();
        $roleId = $clienteRole ? $clienteRole->id : null;

        if (!$roleId) {
            return response()->json(['message' => 'Rol de cliente no encontrado en el sistema'], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleId,
            'active' => 1
        ]);

        // Asegurar que devuelve la relación role para mandar al frontend el string del rol
        $user->load('role');

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'cliente'
            ]
        ], 201);
    }
}

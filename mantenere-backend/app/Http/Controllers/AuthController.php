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

        // Normalizar el nombre del rol para el frontend
        $roleName = strtolower($user->role->name);

        // Sub-Admin → mismo panel que admin, diferencia controlada por hierarchy_level
        if ($roleName === 'sub-admin') {
            $roleName = 'admin';
        }

        // Admin Autónomo → panel propio /autonomo
        if ($roleName === 'admin-autonomo') {
            $roleName = 'autonomo';
        }

        // Calcular el admin_autonomo_id efectivo
        $effectiveAdminAutonomoId = $user->admin_autonomo_id;
        if (!$effectiveAdminAutonomoId) {
            if ($roleName === 'encargado' && $user->negocio_id) {
                $negocio = \App\Models\Negocio::find($user->negocio_id);
                if ($negocio) {
                    $effectiveAdminAutonomoId = $negocio->admin_autonomo_id;
                }
            } elseif ($roleName === 'tecnico') {
                $trabajador = \App\Models\Trabajador::where('user_id', $user->id)->first();
                if ($trabajador) {
                    $effectiveAdminAutonomoId = $trabajador->admin_autonomo_id;
                }
            } elseif ($roleName === 'cliente') {
                $negocio = \App\Models\Negocio::where('user_id', $user->id)->first();
                if ($negocio) {
                    $effectiveAdminAutonomoId = $negocio->admin_autonomo_id;
                }
            }
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $roleName,
                'negocio_id' => $user->negocio_id,
                'admin_autonomo_id' => $effectiveAdminAutonomoId,
                'cv_url'     => $user->cv_url,
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

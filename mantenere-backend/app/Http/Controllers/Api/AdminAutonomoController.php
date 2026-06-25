<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Negocio;
use App\Models\Trabajador;
use App\Models\Trabajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAutonomoController extends Controller
{
    /**
     * Lista todos los Admin Autónomos (solo accesible por Admin/Root)
     */
    public function index(Request $request)
    {
        $autonomoRole = \App\Models\Role::where('name', 'admin-autonomo')->first();
        if (!$autonomoRole) {
            return response()->json([]);
        }

        $autonomos = User::with('role')
            ->where('role_id', $autonomoRole->id)
            ->get()
            ->map(function ($u) {
                $negocios   = Negocio::where('admin_autonomo_id', $u->id)->count();
                $tecnicos   = Trabajador::where('admin_autonomo_id', $u->id)->count();
                $trabajos   = Trabajo::where('admin_autonomo_id', $u->id)->count();
                return array_merge($u->toArray(), [
                    'stats' => [
                        'negocios' => $negocios,
                        'tecnicos' => $tecnicos,
                        'trabajos' => $trabajos,
                    ]
                ]);
            });

        return response()->json($autonomos);
    }

    /**
     * Dashboard / stats de un Admin Autónomo específico
     */
    public function dashboard($id)
    {
        $autonomo = User::find($id);
        if (!$autonomo) {
            return response()->json(['message' => 'Admin Autónomo no encontrado'], 404);
        }

        $negocios  = Negocio::where('admin_autonomo_id', $id)->count();
        $tecnicos  = Trabajador::where('admin_autonomo_id', $id)->count();
        $trabajos  = Trabajo::where('admin_autonomo_id', $id)->count();

        $trabajosPorEstado = Trabajo::where('admin_autonomo_id', $id)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        return response()->json([
            'admin'  => [
                'id'    => $autonomo->id,
                'name'  => $autonomo->name,
                'email' => $autonomo->email,
                'active'=> $autonomo->active,
            ],
            'stats'  => [
                'negocios'  => $negocios,
                'tecnicos'  => $tecnicos,
                'trabajos'  => $trabajos,
            ],
            'trabajos_por_estado' => $trabajosPorEstado,
        ]);
    }

    /**
     * Negocios del Admin Autónomo
     */
    public function negocios($id)
    {
        $negocios = Negocio::with('areas.equipos')
            ->where('admin_autonomo_id', $id)
            ->get();
        return response()->json($negocios);
    }

    /**
     * Trabajadores (técnicos) del Admin Autónomo
     */
    public function trabajadores($id)
    {
        $trabajadores = Trabajador::with('user')
            ->where('admin_autonomo_id', $id)
            ->get();
        return response()->json($trabajadores);
    }

    /**
     * Trabajos del Admin Autónomo
     */
    public function trabajos($id)
    {
        $trabajos = Trabajo::with(['trabajador', 'negocio', 'reporte'])
            ->where('admin_autonomo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($trabajos);
    }

    /**
     * Cotizaciones del Admin Autónomo
     */
    public function cotizaciones($id)
    {
        $cotizaciones = \App\Models\Cotizacion::with('trabajo')
            ->where('admin_autonomo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($cotizaciones);
    }

    /**
     * Bloquear / Desbloquear un Admin Autónomo
     */
    public function toggleBloqueo($id)
    {
        $autonomo = User::find($id);
        if (!$autonomo) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $autonomo->active = $autonomo->active ? 0 : 1;
        $autonomo->save();

        return response()->json([
            'message' => $autonomo->active ? 'Admin Autónomo activado' : 'Admin Autónomo bloqueado',
            'active'  => $autonomo->active,
        ]);
    }

    /**
     * Obtener el Gerente General del Admin Autónomo actual
     */
    public function getGerenteGeneral(Request $request)
    {
        $user = $request->user();
        $adminId = $user->admin_autonomo_id ?? $user->id;

        $roleGerente = \App\Models\Role::where('name', 'gerente-general')->first();
        if (!$roleGerente) {
            return response()->json(['gerente' => null]);
        }

        $gerente = User::where('admin_autonomo_id', $adminId)
                       ->where('role_id', $roleGerente->id)
                       ->first();

        return response()->json([
            'gerente' => $gerente ? [
                'name' => $gerente->name,
                'email' => $gerente->email,
            ] : null
        ]);
    }

    /**
     * Asignar o actualizar el Gerente General del Admin Autónomo actual
     */
    public function asignarGerenteGeneral(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'password' => 'required|min:8',
        ]);

        $user = $request->user();
        // Solo el admin autonomo principal puede crear su gerente general, pero si un gerente general intenta esto,
        // podríamos bloquearlo. El usuario especificó: "por el momento solo un gerente general por admin autonomo".
        // Vamos a permitir solo a admin-autonomo asignar a su gerente.
        if (strtolower($user->role->name) !== 'admin-autonomo') {
            return response()->json(['message' => 'Solo el Admin Autónomo puede asignar un gerente.'], 403);
        }

        $roleGerente = \App\Models\Role::where('name', 'gerente-general')->first();
        if (!$roleGerente) {
            return response()->json(['message' => 'Rol de gerente general no existe en el sistema'], 500);
        }

        $gerente = User::where('admin_autonomo_id', $user->id)
                       ->where('role_id', $roleGerente->id)
                       ->first();

        $plainPassword = $request->password;

        if ($gerente) {
            // Actualizar datos
            $gerente->update([
                'email' => $request->email,
                'name' => $request->name,
                'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
            ]);
        } else {
            // Validar que el correo no esté en uso
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json(['message' => 'El correo ya está en uso por otro usuario'], 422);
            }

            // Crear nuevo gerente
            $gerente = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
                'role_id' => $roleGerente->id,
                'admin_autonomo_id' => $user->id,
                'active' => 1
            ]);
        }

        return response()->json([
            'message' => 'Gerente General asignado correctamente',
            'gerente' => $gerente
        ]);
    }
}

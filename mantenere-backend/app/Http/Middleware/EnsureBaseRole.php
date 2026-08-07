<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite solo roles del ecosistema BASE:
 *   0 = root
 *   1 = Admin
 *   2 = Cliente
 *   3 = tecnico-normal
 *
 * Bloquea cualquier rol del ecosistema autónomo (level >= 4).
 * Excepción: root (level 0) puede acceder a todo.
 */
class EnsureBaseRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $level = $user->role->hierarchy_level;

        // Root puede acceder a todo
        if ($level === 0) {
            return $next($request);
        }

        // Solo niveles 1-3 pertenecen al ecosistema base
        if ($level > 3) {
            return response()->json([
                'message' => 'Acceso denegado. Esta ruta es exclusiva del ecosistema base.'
            ], 403);
        }

        return $next($request);
    }
}

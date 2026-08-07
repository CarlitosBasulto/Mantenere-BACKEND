<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite solo roles del ecosistema AUTÓNOMO:
 *   4 = propietario-autonomo
 *   5 = administrador-general
 *   6 = gerente-sucursal
 *   7 = tecnico-autonomo
 *
 * Bloquea roles del ecosistema base (level 1-3).
 * Excepción: root (level 0) y Admin (level 1) pueden supervisar el ecosistema autónomo.
 */
class EnsureAutonomoRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $level = $user->role->hierarchy_level;

        // Root (0) y Admin (1) pueden supervisar el ecosistema autónomo
        if ($level <= 1) {
            return $next($request);
        }

        // Solo niveles 4-7 pertenecen al ecosistema autónomo
        if ($level < 4) {
            return response()->json([
                'message' => 'Acceso denegado. Esta ruta es exclusiva del ecosistema autónomo.'
            ], 403);
        }

        return $next($request);
    }
}

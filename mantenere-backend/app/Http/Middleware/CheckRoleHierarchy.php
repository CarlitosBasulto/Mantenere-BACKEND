<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckRoleHierarchy
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->role) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Root puede todo
        if ($authUser->role->hierarchy_level === 0) {
            return $next($request);
        }

        $targetUser = $request->route('user');

        // Si existe usuario objetivo (route model binding)
        if ($targetUser instanceof User) {

            if (!$targetUser->role) {
                return response()->json(['message' => 'Usuario inválido'], 404);
            }

            if ($targetUser->role->hierarchy_level < $authUser->role->hierarchy_level) {
                return response()->json([
                    'message' => 'No tienes permisos para acceder a este usuario'
                ], 403);
            }
        }

        return $next($request);
    }
}

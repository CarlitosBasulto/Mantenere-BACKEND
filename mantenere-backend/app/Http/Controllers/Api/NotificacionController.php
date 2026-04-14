<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    // 🔔 1. Obtener todas las notificaciones de un usuario
    public function indexByUsuario($user_id)
    {
        $notificaciones = Notificacion::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->limit(30) // Limitamos a las últimas 30 por rendimiento
            ->get();

        return response()->json($notificaciones);
    }

    // 📩 2. Crear una nueva notificación
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
        ]);

        $notificacion = Notificacion::create([
            'user_id' => $request->user_id,
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'enlace' => $request->enlace,
            'leido' => false
        ]);

        return response()->json([
            'message' => 'Notificación creada exitosamente',
            'data' => $notificacion
        ], 201);
    }

    // ✅ 3. Marcar una sóla notificación como leída
    public function markAsRead($id)
    {
        $notificacion = Notificacion::findOrFail($id);
        $notificacion->update(['leido' => true]);

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'data' => $notificacion
        ]);
    }

    // 🧹 4. Marcar TODAS las de un usuario como leídas
    public function markAllAsRead($user_id)
    {
        Notificacion::where('user_id', $user_id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return response()->json([
            'message' => 'Todas las notificaciones fueron marcadas como leídas'
        ]);
    }

    /**
     * 📣 5. Notificar a todos los usuarios de un ROL específico (p.ej. 'admin')
     * Útil para notificar a todos los jefes cuando un técnico termina algo.
     */
    public function notifyByRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'titulo' => 'required|string',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string',
        ]);

        $users = \App\Models\User::whereHas('role', function($query) use ($request) {
            $query->where('name', $request->role);
        })->get();

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = Notificacion::create([
                'user_id' => $user->id,
                'titulo' => $request->titulo,
                'mensaje' => $request->mensaje,
                'enlace' => $request->enlace,
                'leido' => false
            ]);
        }

        return response()->json([
            'message' => 'Notificaciones enviadas al rol ' . $request->role,
            'count' => count($notifications)
        ]);
    }
}

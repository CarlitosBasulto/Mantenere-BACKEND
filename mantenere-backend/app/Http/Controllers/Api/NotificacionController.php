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
        // Traemos las notificaciones del usuario, ordenadas por más reciente
        $notificaciones = Notificacion::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notificaciones);
    }

    // 📩 2. Crear una nueva notificación (Este método lo usarás desde PHP cuando algo suceda, o si React quiere disparar una)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'mensaje' => 'required|string',
        ]);

        $notificacion = Notificacion::create([
            'user_id' => $request->user_id,
            'mensaje' => $request->mensaje,
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

    // 🧹 4. (Opcional pero recomendado para el Menu.tsx) Marcar TODAS las de un usuario como leídas
    public function markAllAsRead($user_id)
    {
        Notificacion::where('user_id', $user_id)->update(['leido' => true]);

        return response()->json([
            'message' => 'Todas las notificaciones fueron marcadas como leídas'
        ]);
    }
}

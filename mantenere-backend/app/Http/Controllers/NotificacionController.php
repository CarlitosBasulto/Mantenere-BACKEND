<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = Notificacion::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Notificacion::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['leido' => true]);

        return response()->json(['message' => 'Notificación marcada como leída.']);
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead()
    {
        Notificacion::where('user_id', auth()->id())
            ->where('leido', false)
            ->update(['leido' => true]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas.']);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = Notificacion::where('user_id', auth()->id())->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada.']);
    }

    /**
     * Static helper to create notifications (internal use).
     */
    public static function createNotification($userId, $titulo, $mensaje, $enlace = null)
    {
        return Notificacion::create([
            'user_id' => $userId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'enlace' => $enlace,
            'leido' => false
        ]);
    }
}

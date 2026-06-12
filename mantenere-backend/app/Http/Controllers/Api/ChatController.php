<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index($trabajoId)
    {
        $chats = \App\Models\TrabajoChat::with('sender:id,name,role_id')->where('trabajo_id', $trabajoId)->orderBy('created_at', 'asc')->get();
        // Cargar role
        $chats->each(function($chat) {
            $chat->sender->load('role:id,name');
        });
        return response()->json($chats);
    }

    public function destroy($trabajoId)
    {
        \App\Models\TrabajoChat::where('trabajo_id', $trabajoId)->delete();
        return response()->json(['message' => 'Chat borrado exitosamente']);
    }

    public function store(Request $request, $trabajoId)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $chat = \App\Models\TrabajoChat::create([
            'trabajo_id' => $trabajoId,
            'sender_id' => $request->user()->id,
            'message' => $request->message
        ]);

        $chat->load('sender:id,name,role_id');
        $chat->sender->load('role:id,name');

        // Podríamos enviar notificación al otro usuario aquí (Encargado -> Admin o Admin -> Encargado)
        // Lo podemos manejar también desde el frontend o aquí. Lo más robusto es aquí:
        $trabajo = \App\Models\Trabajo::with('negocio')->findOrFail($trabajoId);
        $roleName = strtolower($request->user()->role->name);
        
        $notifyUserId = null;
        if ($roleName === 'admin-autonomo') {
            // Notificar al encargado
            $roleEncargado = \App\Models\Role::where('name', 'encargado')->first();
            $encargado = \App\Models\User::where('negocio_id', $trabajo->negocio_id)
                                         ->where('role_id', $roleEncargado->id)
                                         ->first();
            if ($encargado) {
                $notifyUserId = $encargado->id;
            }
        } elseif ($roleName === 'encargado') {
            // Notificar al admin-autonomo
            if ($trabajo->negocio && $trabajo->negocio->admin_autonomo_id) {
                $notifyUserId = $trabajo->negocio->admin_autonomo_id;
            }
        }

        if ($notifyUserId) {
            \App\Models\Notification::create([
                'user_id' => $notifyUserId,
                'type' => 'nuevo_mensaje',
                'data' => [
                    'trabajo_id' => $trabajoId,
                    'message' => 'Nuevo mensaje en la solicitud #' . $trabajoId
                ],
                'is_read' => false
            ]);
        }

        return response()->json($chat);
    }
}

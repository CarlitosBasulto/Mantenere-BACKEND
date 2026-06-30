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
            'message' => 'required|string',
            'is_quote' => 'boolean',
            'quote_amount' => 'numeric|nullable'
        ]);

        $chat = \App\Models\TrabajoChat::create([
            'trabajo_id' => $trabajoId,
            'sender_id' => $request->user()->id,
            'message' => $request->message,
            'is_quote' => $request->boolean('is_quote', false),
            'quote_amount' => $request->quote_amount
        ]);

        $chat->load('sender:id,name,role_id');
        $chat->sender->load('role:id,name');

        // Notificar a las otras partes involucradas
        $trabajo = \App\Models\Trabajo::with('negocio')->findOrFail($trabajoId);
        $senderId = $request->user()->id;
        
        $usersToNotify = [];
        
        // 1. Técnico
        if ($trabajo->trabajador_id && $trabajo->trabajador_id != $senderId) {
            $tecnico = \App\Models\Trabajador::find($trabajo->trabajador_id);
            if ($tecnico && $tecnico->user_id != $senderId) {
                $usersToNotify[] = $tecnico->user_id;
            }
        }
        
        // 2. Subgerente (Encargado del negocio)
        if ($trabajo->negocio && $trabajo->negocio->encargado_id && $trabajo->negocio->encargado_id != $senderId) {
            $usersToNotify[] = $trabajo->negocio->encargado_id;
        }

        // 3. Admin / Gerente General (quien haya creado el ecosistema o sea admin)
        if ($trabajo->admin_autonomo_id && $trabajo->admin_autonomo_id != $senderId) {
            $usersToNotify[] = $trabajo->admin_autonomo_id;
        }


        foreach ($usersToNotify as $uid) {
            \App\Models\Notificacion::create([
                'user_id' => $uid,
                'titulo' => '💬 Nuevo mensaje en el chat',
                'mensaje' => 'Nuevo mensaje de ' . $request->user()->name . ' en la solicitud #' . $trabajoId,
                'enlace' => null, // El chat es flotante, así que pueden abrirlo desde donde estén si tienen el ID
                'leido' => false
            ]);
        }

        return response()->json($chat);
    }

    public function quoteAction(Request $request, $trabajoId)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'reason' => 'nullable|string'
        ]);

        $trabajo = \App\Models\Trabajo::findOrFail($trabajoId);
        $user = $request->user();

        if ($request->action === 'accept') {
            $trabajo->estado = 'Trabajo';
            $trabajo->save();
            $message = 'Ha aceptado la propuesta. ¡El trabajo ha iniciado!';
        } else {
            $message = 'Ha rechazado la propuesta. Motivo: ' . $request->reason;
        }

        $chat = \App\Models\TrabajoChat::create([
            'trabajo_id' => $trabajoId,
            'sender_id' => $user->id,
            'message' => $message,
            'is_quote' => false,
            'quote_amount' => null
        ]);

        $chat->load('sender:id,name,role_id');
        $chat->sender->load('role:id,name');

        return response()->json([
            'message' => 'Acción registrada con éxito',
            'chat' => $chat,
            'trabajo' => $trabajo
        ]);
    }
}

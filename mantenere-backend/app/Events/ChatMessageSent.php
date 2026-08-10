<?php

namespace App\Events;

use App\Models\TrabajoChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;

    public function __construct(TrabajoChat $chat)
    {
        $this->chat = $chat;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trabajo.' . $this->chat->trabajo_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageSent';
    }
}

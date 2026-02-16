<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $messaggio;

    public function __construct(ChatMessage $messaggio)
    {
        $this->messaggio = $messaggio;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->messaggio->thread_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->messaggio->thread_id,
            'message_id' => $this->messaggio->id,
            'user_id' => $this->messaggio->user_id,
            'messaggio' => $this->messaggio->messaggio,
            'created_at' => $this->messaggio->created_at?->toIso8601String(),
        ];
    }
}

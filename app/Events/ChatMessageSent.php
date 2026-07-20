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
            new PrivateChannel('chat.'.$this->messaggio->thread_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $mittente = $this->messaggio->relationLoaded('mittente')
            ? $this->messaggio->mittente
            : $this->messaggio->mittente()->first();

        return [
            'thread_id' => (int) $this->messaggio->thread_id,
            'message_id' => (int) $this->messaggio->id,
            'user_id' => (int) $this->messaggio->user_id,
            'sender' => $mittente?->nominativo(),
            'messaggio' => $this->messaggio->messaggio,
            'priority' => (int) ($this->messaggio->priority ?? 0),
            'reply_to_id' => $this->messaggio->reply_to_id ? (int) $this->messaggio->reply_to_id : null,
            'has_attachments' => $this->messaggio->relationLoaded('allegati')
                ? $this->messaggio->allegati->isNotEmpty()
                : $this->messaggio->allegati()->exists(),
            'created_at' => $this->messaggio->created_at?->toIso8601String(),
        ];
    }
}

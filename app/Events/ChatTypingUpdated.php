<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatTypingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $threadId;

    public int $userId;

    public ?string $userName;

    public bool $typing;

    public function __construct(int $threadId, int $userId, ?string $userName, bool $typing)
    {
        $this->threadId = $threadId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->typing = $typing;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->threadId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.typing.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'user_id' => $this->userId,
            'name' => $this->userName,
            'typing' => $this->typing,
        ];
    }
}

<?php

namespace App\Jobs;

use App\Services\ChatWebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $userId,
        protected array $payload
    ) {
        $this->onQueue('default');
    }

    public function handle(ChatWebPushService $service): void
    {
        $service->sendToUser($this->userId, $this->payload);
    }
}

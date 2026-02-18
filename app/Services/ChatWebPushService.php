<?php

namespace App\Services;

use App\Models\ChatPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class ChatWebPushService
{
    public function isConfigured(): bool
    {
        return !empty(config('services.webpush.vapid.public_key'))
            && !empty(config('services.webpush.vapid.private_key'))
            && !empty(config('services.webpush.vapid.subject'));
    }

    public function sendToUser(int $userId, array $payload): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $subscriptions = ChatPushSubscription::query()
            ->where('user_id', $userId)
            ->where('is_enabled', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => (string) config('services.webpush.vapid.subject'),
                'publicKey' => (string) config('services.webpush.vapid.public_key'),
                'privateKey' => (string) config('services.webpush.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth, [
            'TTL' => 180,
        ]);

        foreach ($subscriptions as $subscription) {
            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->public_key,
                    'auth' => $subscription->auth_token,
                ],
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ]);

            $webPush->queueNotification($sub, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()?->getUri()?->__toString();
            if (!$endpoint) {
                continue;
            }

            if ($report->isSuccess()) {
                ChatPushSubscription::query()
                    ->where('user_id', $userId)
                    ->where('endpoint', $endpoint)
                    ->update(['last_used_at' => now()]);
                continue;
            }

            $statusCode = $report->getResponse()?->getStatusCode();
            if (in_array((int) $statusCode, [404, 410], true)) {
                ChatPushSubscription::query()
                    ->where('user_id', $userId)
                    ->where('endpoint', $endpoint)
                    ->delete();
            }
        }
    }
}

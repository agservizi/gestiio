<?php

namespace App\Services;

use App\Models\AiEvent;
use App\Models\AiAction;
use App\Models\AiSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAutomationService
{
    public function dispatch(
        string $eventType,
        array $payload = [],
        ?User $user = null,
        ?Model $subject = null,
        string $audience = 'agente'
    ): AiEvent {
        $event = AiEvent::create([
            'user_id' => $user?->id,
            'audience' => $audience,
            'event_type' => $eventType,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'status' => config('services.n8n.enabled') ? 'queued' : 'local_only',
            'payload' => $this->buildPayload($eventType, $payload, $user, $subject, $audience),
        ]);

        if (! config('services.n8n.enabled') || ! config('services.n8n.webhook_url')) {
            $this->createLocalFallbackSuggestion($event);

            return $event;
        }

        try {
            $response = Http::timeout((int) config('services.n8n.timeout', 12))
                ->withHeaders([
                    'X-Gestiio-AI-Secret' => (string) config('services.n8n.webhook_secret'),
                    'Accept' => 'application/json',
                ])
                ->post((string) config('services.n8n.webhook_url'), [
                    'event_id' => $event->id,
                    'event_type' => $eventType,
                    'audience' => $audience,
                    'payload' => $event->payload,
                ]);

            $event->status = $response->successful() ? 'sent' : 'failed';
            $event->sent_at = now();
            $event->response = [
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ];
            $event->error_message = $response->successful() ? null : mb_substr($response->body(), 0, 1000);
            $event->save();
        } catch (\Throwable $exception) {
            $event->status = 'failed';
            $event->error_message = $exception->getMessage();
            $event->save();

            Log::warning('Invio evento AI a n8n fallito', [
                'event_id' => $event->id,
                'message' => $exception->getMessage(),
            ]);
        }

        if ($event->status === 'failed') {
            $this->createLocalFallbackSuggestion($event);
        }

        return $event;
    }

    public function dispatchOncePerWindow(
        string $eventType,
        array $payload = [],
        ?User $user = null,
        ?Model $subject = null,
        string $audience = 'agente',
        int $minutes = 60
    ): ?AiEvent {
        $existing = AiEvent::query()
            ->where('event_type', $eventType)
            ->where('audience', $audience)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when($subject, fn ($q) => $q->where('subject_type', get_class($subject))->where('subject_id', $subject->getKey()))
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->first();

        if ($existing) {
            return null;
        }

        return $this->dispatch($eventType, $payload, $user, $subject, $audience);
    }

    public function createSuggestionFromWebhook(array $data): AiSuggestion
    {
        $event = ! empty($data['event_id']) ? AiEvent::find($data['event_id']) : null;
        $suggestionData = $data['suggestion'] ?? $data;
        $userId = $suggestionData['user_id'] ?? $event?->user_id;
        $audience = $suggestionData['audience'] ?? $event?->audience ?? 'agente';
        $scope = $suggestionData['scope'] ?? 'dashboard';
        $title = $suggestionData['title'] ?? 'Consiglio';
        $nextAction = $suggestionData['next_action'] ?? null;
        $signature = $this->suggestionSignature($userId, $audience, $scope, $title, $nextAction);
        $metadata = $suggestionData['metadata'] ?? [];

        $existing = AiSuggestion::query()
            ->where('audience', $audience)
            ->where('scope', $scope)
            ->when($userId, fn ($q) => $q->where('user_id', $userId), fn ($q) => $q->whereNull('user_id'))
            ->where(function ($q) use ($signature, $title, $nextAction) {
                $q->where('metadata->signature', $signature)
                    ->orWhere(function ($nested) use ($title, $nextAction) {
                        $nested->where('title', $title)
                            ->where('next_action', $nextAction);
                    });
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('id')
            ->first();

        if ($existing) {
            if ($event) {
                $event->status = 'completed';
                $event->completed_at = now();
                $event->response = array_merge($event->response ?? [], [
                    'duplicate_suggestion_id' => $existing->id,
                ]);
                $event->save();
            }

            return $existing;
        }

        $suggestion = AiSuggestion::create([
            'ai_event_id' => $event?->id,
            'user_id' => $userId,
            'audience' => $audience,
            'scope' => $scope,
            'subject_type' => $suggestionData['subject_type'] ?? $event?->subject_type,
            'subject_id' => $suggestionData['subject_id'] ?? $event?->subject_id,
            'priority' => $suggestionData['priority'] ?? 'media',
            'status' => 'new',
            'title' => $title,
            'summary' => $suggestionData['summary'] ?? null,
            'next_action' => $nextAction,
            'actions' => $suggestionData['actions'] ?? [],
            'metadata' => array_merge($metadata, ['signature' => $signature]),
        ]);

        if ($event) {
            $event->status = 'completed';
            $event->completed_at = now();
            $event->save();
        }

        return $suggestion;
    }

    protected function suggestionSignature(?int $userId, string $audience, string $scope, string $title, ?string $nextAction): string
    {
        $parts = [
            $userId ?: 'global',
            $audience,
            $scope,
            mb_strtolower(trim(preg_replace('/\s+/', ' ', $title))),
            mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $nextAction))),
        ];

        return sha1(implode('|', $parts));
    }

    protected function buildPayload(string $eventType, array $payload, ?User $user, ?Model $subject, string $audience): array
    {
        return array_merge($payload, [
            'context' => [
                'app' => 'gestiio',
                'audience' => $audience,
                'event_type' => $eventType,
                'generated_at' => now()->toIso8601String(),
                'user' => $user ? [
                    'id' => $user->id,
                    'nome' => trim((string) $user->nominativo()),
                    'email' => $user->email,
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [],
                    'usage_profile' => $this->usageProfile($user),
                ] : null,
                'subject' => $subject ? [
                    'type' => get_class($subject),
                    'id' => $subject->getKey(),
                ] : null,
            ],
        ]);
    }

    public function usageProfile(User $user): array
    {
        $lastActions = AiAction::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('action_type, count(*) as totale')
            ->groupBy('action_type')
            ->pluck('totale', 'action_type');

        $accepted = (int) ($lastActions['accepted'] ?? 0);
        $dismissed = (int) ($lastActions['dismissed'] ?? 0);
        $clicked = (int) ($lastActions['clicked'] ?? 0);
        $total = max(1, $accepted + $dismissed + $clicked);

        return [
            'accepted' => $accepted,
            'dismissed' => $dismissed,
            'clicked' => $clicked,
            'acceptance_rate' => round(($accepted / $total) * 100, 1),
            'preference' => $accepted >= $dismissed ? 'azioni_dirette' : 'consigli_piu_selettivi',
        ];
    }

    protected function createLocalFallbackSuggestion(AiEvent $event): void
    {
        if (AiSuggestion::query()->where('ai_event_id', $event->id)->exists()) {
            return;
        }

        $this->createSuggestionFromWebhook([
            'event_id' => $event->id,
            'suggestion' => [
                'user_id' => $event->user_id,
                'audience' => $event->audience,
                'scope' => 'dashboard',
                'subject_type' => $event->subject_type,
                'subject_id' => $event->subject_id,
                'priority' => 'media',
                'status' => 'new',
                'title' => 'Analisi in preparazione',
                'summary' => 'Ho registrato l’attività recente. Il consiglio sarà disponibile appena arrivano i dati.',
                'next_action' => 'Controlla la dashboard e riprova tra qualche minuto.',
                'actions' => [],
                'metadata' => ['fallback' => true],
            ],
        ]);
    }
}

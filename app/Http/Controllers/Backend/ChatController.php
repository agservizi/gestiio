<?php

namespace App\Http\Controllers\Backend;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Jobs\SendChatWebPushNotification;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\ChatMessageAudit;
use App\Models\ChatMessageFavorite;
use App\Models\ChatMessageMention;
use App\Models\ChatMessagePin;
use App\Models\ChatPushSubscription;
use App\Models\ChatMessageReaction;
use App\Models\ChatQuickTemplate;
use App\Models\ChatThread;
use App\Models\ChatThreadUser;
use App\Models\User;
use App\Notifications\NotificaPrimoMessaggioChatInterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $threads = $this->threadsPerUtente($authUser->id);
        $lastNotificationMessageId = $this->ultimoMessaggioNotificaId($threads, $authUser->id);

        $threadId = $request->integer('thread');
        $threadAttivo = null;

        if ($threadId) {
            $threadAttivo = $threads->firstWhere('id', $threadId);
            if ($threadAttivo && isset($threadAttivo->can_chat) && !$threadAttivo->can_chat) {
                $threadAttivo = null;
            }
        }

        if (!$threadAttivo) {
            $threadAttivo = $threads->first(function (ChatThread $thread) {
                return !isset($thread->can_chat) || (bool) $thread->can_chat;
            });
        }

        $messaggi = collect();
        $pinnedMessages = collect();
        if ($threadAttivo) {
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $messaggi = $this->messaggiThread($threadAttivo->id);
            $pinnedMessages = $this->messaggiPinnatiThread($threadAttivo->id);
        }

        // Timestamp di lettura dell'altro partecipante (per le conferme di lettura)
        $altroLastReadAt = null;
        if ($threadAttivo) {
            $altroLastReadAt = $this->altroLastReadAt($threadAttivo->id, $authUser->id);
        }

        // Aggiorna stato online dell'utente corrente
        $this->aggiornaStatoOnline($authUser->id);

        $utentiDisponibili = $this->utentiDisponibili($authUser);
        $mentionUsers = $utentiDisponibili
            ->map(function (User $utente) {
                return [
                    'id' => (int) $utente->id,
                    'name' => $utente->nominativo(),
                    'tag' => Str::slug($utente->nominativo(), '.'),
                ];
            })
            ->values();

        return view('Backend.Chat.index', [
            'controller' => get_class($this),
            'titoloPagina' => 'Chat interna',
            'threads' => $threads,
            'threadAttivo' => $threadAttivo,
            'messaggi' => $messaggi,
            'utentiDisponibili' => $utentiDisponibili,
            'mentionUsers' => $mentionUsers,
            'altroLastReadAt' => $altroLastReadAt,
            'quickTemplates' => $this->quickTemplatesData($authUser->id),
            'pinnedMessages' => $pinnedMessages,
            'lastNotificationMessageId' => $lastNotificationMessageId,
        ]);
    }

    public function storeThread(Request $request): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'destinatario_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $destinatario = User::findOrFail($request->integer('destinatario_id'));
        abort_unless($this->puoConversare($authUser, $destinatario), 403);

        $thread = $this->trovaThreadDueUtenti($authUser->id, $destinatario->id);

        if (!$thread) {
            $thread = new ChatThread();
            $thread->created_by = $authUser->id;
            $thread->save();

            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $destinatario->id => ['last_read_at' => null],
            ]);
        }

        return redirect()->action([self::class, 'index'], ['thread' => $thread->id]);
    }

    public function messages(ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);
        $this->ensureThreadConversazioneConsentita($thread->id, $authUser);

        $this->segnaComeLetto($thread->id, $authUser->id);
        $this->segnaComeConsegnato($thread->id, $authUser->id);

        $beforeId = $requestBeforeId = request()->integer('before_id');
        $messaggi = $this->messaggiThread($thread->id, $beforeId, 50);
        $altroLastReadAt = $this->altroLastReadAt($thread->id, $authUser->id);
        $pinnedMessages = $this->messaggiPinnatiThread($thread->id);

        $oldestId = $messaggi->first()?->id;
        $hasMore = false;
        if ($oldestId) {
            $hasMore = ChatMessage::query()
                ->where('thread_id', $thread->id)
                ->where('id', '<', $oldestId)
                ->exists();
        }

        return response()->json([
            'html' => view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
                'altroLastReadAt' => $altroLastReadAt,
            ])->render(),
            'ultimoId' => $messaggi->last()?->id,
            'oldestId' => $oldestId,
            'hasMore' => $hasMore,
            'isPrepend' => (bool) $requestBeforeId,
            'pinnedHtml' => view('Backend.Chat._pinned', [
                'pinnedMessages' => $pinnedMessages,
            ])->render(),
        ]);
    }

    public function sendMessage(Request $request, ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);
        $this->ensureThreadConversazioneConsentita($thread->id, $authUser);

        $request->validate([
            'messaggio' => ['nullable', 'string', 'max:3000', 'required_without:allegati'],
            'allegati' => ['nullable', 'array'],
            'allegati.*' => ['file', 'max:10240'],
            'reply_to_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $destinatariEmailPrimoNonLetto = [];
        $partecipazioniDestinatari = ChatThreadUser::query()
            ->where('thread_id', $thread->id)
            ->where('user_id', '<>', $authUser->id)
            ->get(['user_id', 'last_read_at']);

        foreach ($partecipazioniDestinatari as $partecipazioneDestinatario) {
            $queryNonLettiPreEsistenti = ChatMessage::query()
                ->where('thread_id', $thread->id)
                ->where('user_id', '<>', (int)$partecipazioneDestinatario->user_id);

            if ($partecipazioneDestinatario->last_read_at) {
                $queryNonLettiPreEsistenti->where('created_at', '>', $partecipazioneDestinatario->last_read_at);
            }

            if (!$queryNonLettiPreEsistenti->exists()) {
                $destinatariEmailPrimoNonLetto[] = (int)$partecipazioneDestinatario->user_id;
            }
        }

        $estensioniBloccate = ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'js', 'jar', 'com', 'scr', 'msi'];
        foreach ($request->file('allegati', []) as $allegato) {
            if (!$allegato) {
                continue;
            }

            $ext = strtolower((string) $allegato->getClientOriginalExtension());
            if (in_array($ext, $estensioniBloccate, true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Tipo file non consentito: ' . $ext,
                ], 422);
            }
        }

        $messaggio = new ChatMessage();
        $messaggio->thread_id = $thread->id;
        $messaggio->user_id = $authUser->id;
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->priority = $request->integer('priority', 0);
        if ($this->haReactionsTable() && $request->filled('reply_to_id')) {
            $messaggio->reply_to_id = $request->input('reply_to_id');
        }
        $messaggio->save();
        $messaggio->load('mittente');

        $allegati = $request->file('allegati', []);
        foreach ($allegati as $allegato) {
            if (!$allegato) {
                continue;
            }

            $ext = strtolower((string) $allegato->getClientOriginalExtension());
            if (in_array($ext, $estensioniBloccate, true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Tipo file non consentito: ' . $ext,
                ], 422);
            }

            $path = $allegato->store('chat-allegati', 'public');

            $recordAllegato = new ChatMessageAttachment();
            $recordAllegato->message_id = $messaggio->id;
            $recordAllegato->filename_originale = $allegato->getClientOriginalName();
            $recordAllegato->path_filename = $path;
            $recordAllegato->mime_type = $allegato->getClientMimeType();
            $recordAllegato->dimensione_file = $allegato->getSize();
            $recordAllegato->scan_status = 'clean';
            $recordAllegato->scan_note = 'Scansione base locale: nessun rischio rilevato';
            $recordAllegato->is_blocked = false;
            $recordAllegato->save();
        }

        $this->registraMenzioni($messaggio);

        $thread->touch();
        $this->segnaComeLetto($thread->id, $authUser->id);

        broadcast(new ChatMessageSent($messaggio))->toOthers();

        if (!empty($destinatariEmailPrimoNonLetto)) {
            User::query()
                ->whereIn('id', $destinatariEmailPrimoNonLetto)
                ->get()
                ->each(function (User $destinatario) use ($messaggio, $thread) {
                    if ($this->threadSilenziatoPerUtente($thread->id, $destinatario->id)) {
                        return;
                    }
                    $destinatario->notify(new NotificaPrimoMessaggioChatInterna($messaggio));
                });
        }

        $destinatariPush = ChatThreadUser::query()
            ->where('thread_id', $thread->id)
            ->where('user_id', '<>', $authUser->id)
            ->pluck('user_id');

        foreach ($destinatariPush as $destinatarioId) {
            $destinatarioId = (int) $destinatarioId;
            if ($this->threadSilenziatoPerUtente($thread->id, $destinatarioId)) {
                continue;
            }

            $threadName = $thread->partecipanti()->where('users.id', $authUser->id)->exists()
                ? ($authUser->nominativo() ?: 'Chat interna')
                : 'Chat interna';

            SendChatWebPushNotification::dispatch($destinatarioId, [
                'title' => $authUser->nominativo() . ' · Chat interna',
                'body' => Str::limit(strip_tags((string) ($messaggio->messaggio ?: '📎 Nuovo allegato in chat')), 120),
                'url' => url('/backend/chat-interna?thread=' . $thread->id),
                'thread_id' => (int) $thread->id,
                'message_id' => (int) $messaggio->id,
                'tag' => 'chat-thread-' . $thread->id,
                'icon' => url('/images/logo_small_icon_only.png'),
                'badge' => url('/images/logo_small_icon_only.png'),
                'thread_name' => $threadName,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Messaggio inviato',
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        // Aggiorna stato online
        $this->aggiornaStatoOnline($authUser->id);

        $threads = $this->threadsPerUtente($authUser->id);
        $threadAttivo = null;
        $messaggi = collect();

        $threadId = $request->integer('thread_id');
        $typingStatus = [
            'active' => false,
            'name' => null,
        ];

        if ($threadId) {
            $threadAttivo = $threads->firstWhere('id', $threadId);
        }

        $altroLastReadAt = null;
        $altroOnline = false;
        $threadMuted = false;
        $activeLastMessageId = null;
        $activeLastMessageSenderId = null;

        if ($threadAttivo) {
            $this->ensureThreadConversazioneConsentita($threadAttivo->id, $authUser);
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $this->segnaComeConsegnato($threadAttivo->id, $authUser->id);
            $messaggi = $this->messaggiThread($threadAttivo->id, null, 50);
            $ultimoMessaggio = $messaggi->last();
            $activeLastMessageId = $ultimoMessaggio?->id;
            $activeLastMessageSenderId = $ultimoMessaggio?->user_id;
            $typingStatus = $this->typingStatus($threadAttivo->id, $authUser->id);
            $altroLastReadAt = $this->altroLastReadAt($threadAttivo->id, $authUser->id);
            $threadMuted = $this->threadSilenziatoPerUtente($threadAttivo->id, $authUser->id);

            // Stato online dell'altro partecipante
            $altroPartecipante = $threadAttivo->partecipanti->firstWhere('id', '!=', $authUser->id);
            if ($altroPartecipante) {
                $altroOnline = $this->isOnline($altroPartecipante->id);
            }
        }

        // Stato online di tutti i partecipanti per i thread nella lista
        $onlineMap = [];
        foreach ($threads as $thread) {
            $altro = $thread->getRelation('altroPartecipante');
            if ($altro) {
                $onlineMap[$altro->id] = $this->isOnline($altro->id);
            }
        }

        $notificationMessage = $this->buildNotificationMessage($threads, $authUser->id);

        return response()->json([
            'threadsHtml' => view('Backend.Chat._threads', [
                'threads' => $threads,
                'threadAttivo' => $threadAttivo,
                'onlineMap' => $onlineMap,
            ])->render(),
            'messaggiHtml' => view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
                'altroLastReadAt' => $altroLastReadAt,
            ])->render(),
            'nonLettiTotali' => ChatThreadUser::conteggioNonLetti($authUser->id),
            'typing' => $typingStatus,
            'altroOnline' => $altroOnline,
            'threadMuted' => $threadMuted,
            'activeLastMessageId' => $activeLastMessageId,
            'activeLastMessageSenderId' => $activeLastMessageSenderId,
            'notificationMessage' => $notificationMessage,
            'pinnedHtml' => view('Backend.Chat._pinned', [
                'pinnedMessages' => $threadAttivo ? $this->messaggiPinnatiThread($threadAttivo->id) : collect(),
            ])->render(),
        ]);
    }

    public function pollGlobalNotifications(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $this->aggiornaStatoOnline($authUser->id);

        $threads = $this->threadsPerUtente($authUser->id);
        $notificationMessage = $this->buildNotificationMessage($threads, $authUser->id);
        $sinceId = $request->integer('since_id');

        if ($notificationMessage && $sinceId > 0 && (int) ($notificationMessage['id'] ?? 0) <= $sinceId) {
            $notificationMessage = null;
        }

        return response()->json([
            'nonLettiTotali' => ChatThreadUser::conteggioNonLetti($authUser->id),
            'notificationMessage' => $notificationMessage,
        ]);
    }

    public function typing(Request $request, ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        $typing = $request->boolean('typing');
        $key = $this->typingCacheKey($thread->id, $authUser->id);

        if ($typing) {
            Cache::put($key, $authUser->nominativo(), now()->addSeconds(8));
        } else {
            Cache::forget($key);
        }

        return response()->json(['ok' => true]);
    }

    public function closeThread(ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        $thread->partecipanti()->detach($authUser->id);

        if (!$thread->partecipanti()->exists()) {
            $thread->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Conversazione chiusa',
        ]);
    }

    public function toggleThreadMute(ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        $partecipazione = ChatThreadUser::query()
            ->where('thread_id', $thread->id)
            ->where('user_id', $authUser->id)
            ->firstOrFail();

        if ($partecipazione->muted_until && $partecipazione->muted_until->isFuture()) {
            $partecipazione->muted_until = null;
        } else {
            $partecipazione->muted_until = now()->addDays(3650);
        }

        $partecipazione->save();

        return response()->json([
            'ok' => true,
            'muted' => (bool) ($partecipazione->muted_until && $partecipazione->muted_until->isFuture()),
        ]);
    }

    public function togglePin(ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        $existing = ChatMessagePin::query()
            ->where('message_id', $message->id)
            ->where('user_id', $authUser->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'pinned' => false]);
        }

        ChatMessagePin::query()->create([
            'message_id' => $message->id,
            'user_id' => $authUser->id,
        ]);

        return response()->json(['ok' => true, 'pinned' => true]);
    }

    public function toggleFavorite(ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        $existing = ChatMessageFavorite::query()
            ->where('message_id', $message->id)
            ->where('user_id', $authUser->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'favorite' => false]);
        }

        ChatMessageFavorite::query()->create([
            'message_id' => $message->id,
            'user_id' => $authUser->id,
        ]);

        return response()->json(['ok' => true, 'favorite' => true]);
    }

    public function updateMessage(Request $request, ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        abort_unless((int) $message->user_id === (int) $authUser->id || $authUser->hasPermissionTo('admin'), 403);

        $request->validate([
            'messaggio' => ['required', 'string', 'max:3000'],
        ]);

        $oldText = (string) $message->messaggio;
        $newText = (string) $request->input('messaggio');

        $message->messaggio = $newText;
        $message->edited_at = now();
        $message->save();

        ChatMessageAudit::query()->create([
            'message_id' => $message->id,
            'user_id' => $authUser->id,
            'azione' => 'edit',
            'old_text' => $oldText,
            'new_text' => $newText,
        ]);

        return response()->json(['ok' => true]);
    }

    public function deleteMessage(ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        abort_unless((int) $message->user_id === (int) $authUser->id || $authUser->hasPermissionTo('admin'), 403);

        $oldText = (string) $message->messaggio;

        $message->messaggio = '[Messaggio eliminato]';
        $message->deleted_at = now();
        $message->save();

        ChatMessageAudit::query()->create([
            'message_id' => $message->id,
            'user_id' => $authUser->id,
            'azione' => 'delete',
            'old_text' => $oldText,
            'new_text' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function forwardMessages(Request $request, ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        $request->validate([
            'message_ids' => ['required', 'array', 'min:1'],
            'message_ids.*' => ['integer'],
            'target_thread_id' => ['required', 'integer'],
        ]);

        $targetThreadId = $request->integer('target_thread_id');
        $this->ensureThreadAccesso($targetThreadId, $authUser->id);

        $sorgente = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->whereIn('id', $request->input('message_ids', []))
            ->with('mittente:id,nome,cognome')
            ->orderBy('id')
            ->get();

        foreach ($sorgente as $origine) {
            $testoMittente = $origine->mittente?->nominativo() ?? 'Utente';
            $contenuto = "[Inoltrato da {$testoMittente}]\n" . (string) $origine->messaggio;

            ChatMessage::query()->create([
                'thread_id' => $targetThreadId,
                'user_id' => $authUser->id,
                'messaggio' => $contenuto,
                'forwarded_from_id' => $origine->id,
                'priority' => (int) $origine->priority,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function quickTemplates(): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        return response()->json([
            'templates' => $this->quickTemplatesData($authUser->id),
        ]);
    }

    public function saveQuickTemplate(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $request->validate([
            'titolo' => ['required', 'string', 'max:120'],
            'contenuto' => ['required', 'string', 'max:3000'],
        ]);

        $template = ChatQuickTemplate::query()->create([
            'user_id' => $authUser->id,
            'titolo' => $request->input('titolo'),
            'contenuto' => $request->input('contenuto'),
            'is_global' => false,
        ]);

        return response()->json([
            'ok' => true,
            'template' => $template,
        ]);
    }

    public function pushVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => (string) config('services.webpush.vapid.public_key'),
            'configured' => !empty(config('services.webpush.vapid.public_key')),
        ]);
    }

    public function subscribePush(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'endpoint' => ['required', 'string', 'max:4000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        ChatPushSubscription::query()->updateOrCreate(
            [
                'user_id' => $authUser->id,
                'endpoint' => (string) $request->input('endpoint'),
            ],
            [
                'public_key' => (string) $request->input('keys.p256dh'),
                'auth_token' => (string) $request->input('keys.auth'),
                'content_encoding' => (string) $request->input('contentEncoding', 'aesgcm'),
                'user_agent' => (string) $request->userAgent(),
                'is_enabled' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribePush(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'endpoint' => ['required', 'string', 'max:4000'],
        ]);

        ChatPushSubscription::query()
            ->where('user_id', $authUser->id)
            ->where('endpoint', (string) $request->input('endpoint'))
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function resolveMention(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'tag' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $rawTag = trim((string) $request->input('tag'));
        $tag = ltrim(Str::lower($rawTag), '@');

        $destinatario = $this->utentiDisponibili($authUser)
            ->first(function (User $utente) use ($tag) {
                return $this->mentionTagForUser($utente) === $tag;
            });

        if (!$destinatario) {
            return response()->json([
                'ok' => false,
                'message' => 'Utente menzionato non trovato o non disponibile',
            ], 404);
        }

        $thread = $this->trovaThreadDueUtenti($authUser->id, $destinatario->id);

        if (!$thread) {
            $thread = new ChatThread();
            $thread->created_by = $authUser->id;
            $thread->save();

            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $destinatario->id => ['last_read_at' => null],
            ]);
        }

        return response()->json([
            'ok' => true,
            'thread_id' => (int) $thread->id,
            'utente' => $destinatario->nominativo(),
        ]);
    }

    protected function ensureRuoloConsentito(User $authUser): void
    {
        abort_unless(
            $authUser->hasPermissionTo('admin') || $authUser->hasAnyPermission(['agente', 'supervisore']),
            403
        );
    }

    protected function ensureThreadAccesso(int $threadId, int $userId): void
    {
        abort_unless(
            ChatThreadUser::query()
                ->where('thread_id', $threadId)
                ->where('user_id', $userId)
                ->exists(),
            403
        );
    }

    protected function ensureThreadConversazioneConsentita(int $threadId, User $authUser): void
    {
        $altriPartecipanti = User::query()
            ->whereIn('id', function ($query) use ($threadId, $authUser) {
                $query->from('chat_thread_users')
                    ->select('user_id')
                    ->where('thread_id', $threadId)
                    ->where('user_id', '<>', $authUser->id);
            })
            ->get(['id', 'nome', 'cognome']);

        abort_unless(
            $altriPartecipanti->every(function (User $utente) use ($authUser) {
                return $this->puoConversare($authUser, $utente);
            }),
            403,
            'Conversazione non consentita per i servizi attivi assegnati.'
        );
    }

    protected function utentiDisponibili(User $authUser)
    {
        if ($authUser->hasPermissionTo('admin')) {
            return User::query()
                ->where('id', '<>', $authUser->id)
                ->whereHas('permissions', function ($query) {
                    $query->whereIn('name', ['agente', 'supervisore']);
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);
        }

        return User::query()
            ->where('id', '<>', $authUser->id)
            ->where(function ($query) {
                $query->whereHas('permissions', function ($permissionQuery) {
                    $permissionQuery->where('name', 'admin');
                })->orWhereHas('permissions', function ($permissionQuery) {
                    $permissionQuery->whereIn('name', ['agente', 'supervisore']);
                });
            })
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cognome'])
            ->filter(function (User $utente) use ($authUser) {
                return $this->puoConversare($authUser, $utente);
            })
            ->values();
    }

    protected function puoConversare(User $utenteA, User $utenteB): bool
    {
        if ($utenteA->id === $utenteB->id) {
            return false;
        }

        $aAdmin = $utenteA->hasPermissionTo('admin');
        $bAdmin = $utenteB->hasPermissionTo('admin');

        $aOperativo = $utenteA->hasAnyPermission(['agente', 'supervisore']);
        $bOperativo = $utenteB->hasAnyPermission(['agente', 'supervisore']);

        if (($aAdmin && $bOperativo) || ($bAdmin && $aOperativo)) {
            return true;
        }

        $aAgente = $utenteA->hasPermissionTo('agente');
        $aSupervisore = $utenteA->hasPermissionTo('supervisore');
        $bAgente = $utenteB->hasPermissionTo('agente');
        $bSupervisore = $utenteB->hasPermissionTo('supervisore');

        $isAgenteSupervisorePair = ($aAgente && $bSupervisore) || ($aSupervisore && $bAgente);
        if (!$isAgenteSupervisorePair) {
            return false;
        }

        return $this->condividonoServizioAttivo($utenteA, $utenteB);
    }

    protected function condividonoServizioAttivo(User $utenteA, User $utenteB): bool
    {
        $serviziA = $this->serviziAttiviUtente($utenteA);
        $serviziB = $this->serviziAttiviUtente($utenteB);

        if ($serviziA->isEmpty() || $serviziB->isEmpty()) {
            return false;
        }

        return $serviziA->intersect($serviziB)->isNotEmpty();
    }

    protected function serviziAttiviUtente(User $utente)
    {
        return $utente->permissions
            ->pluck('name')
            ->filter(function ($name) {
                return is_string($name) && Str::startsWith($name, 'servizio_');
            })
            ->values();
    }

    protected function trovaThreadDueUtenti(int $utenteA, int $utenteB): ?ChatThread
    {
        return ChatThread::query()
            ->whereHas('partecipanti', function ($query) use ($utenteA) {
                $query->where('users.id', $utenteA);
            })
            ->whereHas('partecipanti', function ($query) use ($utenteB) {
                $query->where('users.id', $utenteB);
            })
            ->whereDoesntHave('partecipanti', function ($query) use ($utenteA, $utenteB) {
                $query->whereNotIn('users.id', [$utenteA, $utenteB]);
            })
            ->latest('id')
            ->first();
    }

    protected function threadsPerUtente(int $userId)
    {
        $utenteCorrente = User::query()->find($userId, ['id', 'nome', 'cognome']);

        $threads = ChatThread::query()
            ->select('chat_threads.*')
            ->join('chat_thread_users as mia_partecipazione', function ($join) use ($userId) {
                $join->on('mia_partecipazione.thread_id', '=', 'chat_threads.id')
                    ->where('mia_partecipazione.user_id', '=', $userId);
            })
            ->with(['partecipanti:id,nome,cognome', 'ultimoMessaggio.mittente:id,nome,cognome'])
            ->selectSub(function ($query) use ($userId) {
                $query->from('chat_messages')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('chat_messages.thread_id', 'chat_threads.id')
                    ->where('chat_messages.user_id', '<>', $userId)
                    ->whereRaw("chat_messages.created_at > COALESCE(mia_partecipazione.last_read_at, '1970-01-01 00:00:00')");
            }, 'unread_count')
            ->orderByDesc(DB::raw('COALESCE((SELECT MAX(cm.created_at) FROM chat_messages cm WHERE cm.thread_id = chat_threads.id), chat_threads.created_at)'))
            ->get();

        if ($this->haColonna('chat_thread_users', 'muted_until')) {
            $threads->each(function (ChatThread $thread) use ($userId) {
                $mutedUntil = ChatThreadUser::query()
                    ->where('thread_id', $thread->id)
                    ->where('user_id', $userId)
                    ->value('muted_until');

                $thread->is_muted = $mutedUntil ? now()->lt($mutedUntil) : false;
            });
        }

        $threads->each(function (ChatThread $thread) use ($userId, $utenteCorrente) {
            $altro = $thread->partecipanti->firstWhere('id', '!=', $userId);
            $thread->setRelation('altroPartecipante', $altro);

            if ($utenteCorrente && $altro instanceof User) {
                $thread->can_chat = $this->puoConversare($utenteCorrente, $altro);
            } else {
                $thread->can_chat = false;
            }
        });

        return $threads;
    }

    protected function segnaComeLetto(int $threadId, int $userId): void
    {
        ChatThreadUser::query()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    protected function typingStatus(int $threadId, int $currentUserId): array
    {
        $partecipanti = ChatThreadUser::query()
            ->where('thread_id', $threadId)
            ->where('user_id', '<>', $currentUserId)
            ->pluck('user_id');

        foreach ($partecipanti as $participantId) {
            $typingName = Cache::get($this->typingCacheKey($threadId, (int)$participantId));
            if ($typingName) {
                return [
                    'active' => true,
                    'name' => $typingName,
                ];
            }
        }

        return [
            'active' => false,
            'name' => null,
        ];
    }

    protected function typingCacheKey(int $threadId, int $userId): string
    {
        return 'chat_typing_' . $threadId . '_' . $userId;
    }

    /* ------------------------------------------------------------------ */
    /*  REAZIONE EMOJI                                                     */
    /* ------------------------------------------------------------------ */

    public function toggleReaction(Request $request, ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        if (!$this->haReactionsTable()) {
            return response()->json(['ok' => false, 'error' => 'Funzionalità non ancora disponibile. Eseguire la migrazione.'], 422);
        }

        $request->validate([
            'emoji' => ['required', 'string', 'max:10'],
        ]);

        $emoji = $request->input('emoji');

        $existing = ChatMessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $authUser->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChatMessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $authUser->id,
                'emoji' => $emoji,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------ */
    /*  RICERCA MESSAGGI                                                   */
    /* ------------------------------------------------------------------ */

    public function search(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:200'],
            'thread_id' => ['nullable', 'integer'],
            'author_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'with_attachments' => ['nullable', 'boolean'],
            'favorites_only' => ['nullable', 'boolean'],
            'mentions_only' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $threadId = $request->integer('thread_id');
        $authorId = $request->integer('author_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $withAttachments = $request->boolean('with_attachments');
        $favoritesOnly = $request->boolean('favorites_only');
        $mentionsOnly = $request->boolean('mentions_only');
        $priority = $request->input('priority');

        // Cerca solo nei thread dell'utente
        $threadIds = ChatThreadUser::query()
            ->where('user_id', $authUser->id)
            ->pluck('thread_id');

        $query = ChatMessage::query()
            ->whereIn('thread_id', $threadIds)
            ->with('mittente:id,nome,cognome')
            ->latest('id')
            ->limit(50);

        if ($q !== '') {
            $query->where('messaggio', 'LIKE', '%' . $q . '%');
        }

        if ($threadId) {
            $query->where('thread_id', $threadId);
        }

        if ($authorId) {
            $query->where('user_id', $authorId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($withAttachments) {
            $query->whereHas('allegati');
        }

        if ($favoritesOnly) {
            $query->whereHas('preferiti', function ($builder) use ($authUser) {
                $builder->where('user_id', $authUser->id);
            });
        }

        if ($mentionsOnly && $this->haTabella('chat_message_mentions')) {
            $query->whereHas('menzioni', function ($builder) use ($authUser) {
                $builder->where('mentioned_user_id', $authUser->id);
            });
        }

        if ($priority !== null && $priority !== '') {
            $query->where('priority', (int) $priority);
        }

        $results = $query->get()->map(function (ChatMessage $msg) {
            return [
                'id' => $msg->id,
                'thread_id' => $msg->thread_id,
                'mittente' => $msg->mittente?->nominativo() ?? 'Utente',
                'messaggio' => $msg->messaggio,
                'data' => $msg->created_at?->format('d/m/Y H:i'),
                'priority' => (int) ($msg->priority ?? 0),
            ];
        });

        return response()->json(['risultati' => $results]);
    }

    /* ------------------------------------------------------------------ */
    /*  ONLINE STATUS                                                      */
    /* ------------------------------------------------------------------ */

    protected function aggiornaStatoOnline(int $userId): void
    {
        Cache::put('chat_online_' . $userId, true, now()->addSeconds(60));
    }

    protected function isOnline(int $userId): bool
    {
        return (bool) Cache::get('chat_online_' . $userId, false);
    }

    /* ------------------------------------------------------------------ */
    /*  READ RECEIPT HELPER                                                */
    /* ------------------------------------------------------------------ */

    protected function altroLastReadAt(int $threadId, int $currentUserId): ?string
    {
        $record = ChatThreadUser::query()
            ->where('thread_id', $threadId)
            ->where('user_id', '<>', $currentUserId)
            ->first(['last_read_at']);

        return $record?->last_read_at?->toDateTimeString();
    }

    protected function messaggiThread(int $threadId, ?int $beforeId = null, int $limit = 200)
    {
        $query = ChatMessage::query()
            ->where('thread_id', $threadId)
            ->with('mittente:id,nome,cognome')
            ->with('allegati');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        if ($this->haTabella('chat_message_pins')) {
            $query->with('pin');
        }

        if ($this->haTabella('chat_message_favorites')) {
            $query->with('preferiti');
        }

        if ($this->haColonna('chat_messages', 'forwarded_from_id')) {
            $query->with('inoltratoDa.mittente:id,nome,cognome');
        }

        // Carica reazioni e reply solo se la migrazione è stata eseguita
        if ($this->haReactionsTable()) {
            $query->with('reazioni')
                  ->with('replyTo.mittente:id,nome,cognome');
        }

        return $query->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Controlla (con cache statica) se la tabella chat_message_reactions esiste.
     */
    protected function haReactionsTable(): bool
    {
        return $this->haTabella('chat_message_reactions');
    }

    protected function haTabella(string $table): bool
    {
        static $cache = [];

        if (!array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table);
        }

        return (bool) $cache[$table];
    }

    protected function haColonna(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return (bool) $cache[$key];
    }

    protected function segnaComeConsegnato(int $threadId, int $currentUserId): void
    {
        if (!$this->haColonna('chat_messages', 'delivered_at')) {
            return;
        }

        ChatMessage::query()
            ->where('thread_id', $threadId)
            ->where('user_id', '<>', $currentUserId)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);
    }

    protected function threadSilenziatoPerUtente(int $threadId, int $userId): bool
    {
        if (!$this->haColonna('chat_thread_users', 'muted_until')) {
            return false;
        }

        $record = ChatThreadUser::query()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->first(['muted_until']);

        return (bool) ($record?->muted_until && $record->muted_until->isFuture());
    }

    protected function quickTemplatesData(int $userId)
    {
        if (!$this->haTabella('chat_quick_templates')) {
            return collect();
        }

        return ChatQuickTemplate::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('is_global', true);
            })
            ->orderByDesc('is_global')
            ->orderBy('titolo')
            ->get(['id', 'titolo', 'contenuto', 'is_global']);
    }

    protected function mentionTagForUser(User $user): string
    {
        return Str::lower(Str::slug($user->nominativo(), '.'));
    }

    protected function messaggiPinnatiThread(int $threadId)
    {
        if (!$this->haTabella('chat_message_pins')) {
            return collect();
        }

        return ChatMessage::query()
            ->where('thread_id', $threadId)
            ->whereHas('pin')
            ->with('mittente:id,nome,cognome')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    protected function ultimoMessaggioNotificaId($threads, int $userId): int
    {
        $payload = $this->buildNotificationMessage($threads, $userId);

        return (int) ($payload['id'] ?? 0);
    }

    protected function buildNotificationMessage($threads, int $userId): ?array
    {
        $threadIds = $threads->pluck('id')->filter()->values();
        if ($threadIds->isEmpty()) {
            return null;
        }

        $message = ChatMessage::query()
            ->whereIn('thread_id', $threadIds)
            ->where('user_id', '<>', $userId)
            ->latest('id')
            ->with('mittente:id,nome,cognome')
            ->first();

        if (!$message) {
            return null;
        }

        $thread = $threads->firstWhere('id', $message->thread_id);
        $threadName = $thread?->getRelation('altroPartecipante')?->nominativo() ?? 'Conversazione';

        return [
            'id' => (int) $message->id,
            'thread_id' => (int) $message->thread_id,
            'thread_name' => $threadName,
            'sender_id' => (int) $message->user_id,
            'sender' => $message->mittente?->nominativo() ?? 'Utente',
            'excerpt' => Str::limit(strip_tags((string) ($message->messaggio ?? '📎 Allegato')), 90),
            'created_at' => $message->created_at?->toDateTimeString(),
        ];
    }

    protected function registraMenzioni(ChatMessage $messaggio): void
    {
        if (!$this->haTabella('chat_message_mentions')) {
            return;
        }

        preg_match_all('/@([a-zA-Z0-9._-]{2,40})/u', (string) $messaggio->messaggio, $matches);
        $tags = collect($matches[1] ?? [])->unique()->values();

        if ($tags->isEmpty()) {
            return;
        }

        $partecipantiIds = ChatThreadUser::query()
            ->where('thread_id', $messaggio->thread_id)
            ->pluck('user_id');

        $utenti = User::query()
            ->whereIn('id', $partecipantiIds)
            ->get(['id', 'nome', 'cognome'])
            ->filter(function (User $utente) use ($tags, $messaggio) {
                if ((int) $utente->id === (int) $messaggio->user_id) {
                    return false;
                }

                $needle = Str::lower(Str::slug($utente->nominativo(), '.'));
                foreach ($tags as $tag) {
                    if (Str::contains($needle, Str::lower((string) $tag))) {
                        return true;
                    }
                }

                return false;
            });

        foreach ($utenti as $utenteMenzionato) {
            ChatMessageMention::query()->firstOrCreate([
                'message_id' => $messaggio->id,
                'mentioned_user_id' => $utenteMenzionato->id,
            ]);

            if (!$this->threadSilenziatoPerUtente($messaggio->thread_id, (int) $utenteMenzionato->id)) {
                $utenteMenzionato->notify(new NotificaPrimoMessaggioChatInterna($messaggio));
            }
        }
    }
}

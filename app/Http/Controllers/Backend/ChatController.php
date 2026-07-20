<?php

namespace App\Http\Controllers\Backend;

use App\Events\ChatMessageSent;
use App\Events\ChatTypingUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\SendChatWebPushNotification;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\ChatMessageAudit;
use App\Models\ChatMessageFavorite;
use App\Models\ChatMessageMention;
use App\Models\ChatMessagePin;
use App\Models\ChatMessageReaction;
use App\Models\ChatPushSubscription;
use App\Models\ChatQuickTemplate;
use App\Models\ChatThread;
use App\Models\ChatThreadUser;
use App\Models\User;
use App\Notifications\NotificaPrimoMessaggioChatInterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $threads = $this->threadsPerUtente($authUser->id, $request->boolean('archived'));
        $lastNotificationMessageId = $this->ultimoMessaggioNotificaId($threads, $authUser->id);

        $threadId = $request->integer('thread');
        $threadAttivo = null;

        if ($threadId) {
            $threadAttivo = $threads->firstWhere('id', $threadId);
            if ($threadAttivo && isset($threadAttivo->can_chat) && ! $threadAttivo->can_chat) {
                $threadAttivo = null;
            }
        }

        if (! $threadAttivo) {
            $threadAttivo = $threads->first(function (ChatThread $thread) {
                return ! isset($thread->can_chat) || (bool) $thread->can_chat;
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

        return response()
            ->view('Backend.Chat.index', [
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
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('CDN-Cache-Control', 'no-store')
            ->header('Cloudflare-CDN-Cache-Control', 'no-store');
    }

    public function storeThread(Request $request): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'destinatario_id' => ['nullable', 'integer', 'exists:users,id'],
            'destinatario_ids' => ['nullable', 'array', 'min:1'],
            'destinatario_ids.*' => ['integer', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        // Accetta sia il formato singolo (destinatario_id) sia quello multiplo (destinatario_ids[])
        $destinatariIds = collect($request->input('destinatario_ids', []))
            ->push($request->input('destinatario_id'))
            ->filter()
            ->map(fn ($valore) => (int) $valore)
            ->reject(fn ($id) => $id === (int) $authUser->id)
            ->unique()
            ->values();

        abort_if($destinatariIds->isEmpty(), 422, 'Selezionare almeno un destinatario.');

        $destinatari = User::query()->whereIn('id', $destinatariIds)->get();
        abort_unless($destinatari->count() === $destinatariIds->count(), 404);

        foreach ($destinatari as $destinatario) {
            abort_unless($this->puoConversare($authUser, $destinatario), 403);
        }

        // Più di un altro partecipante ⇒ gruppo
        $isGruppo = $destinatari->count() > 1;

        if ($isGruppo) {
            $nomeGruppo = trim((string) $request->input('name', '')) ?: ('Gruppo '.$authUser->nominativo());

            $thread = DB::transaction(function () use ($authUser, $destinatari, $nomeGruppo) {
                $thread = new ChatThread;
                $thread->created_by = $authUser->id;
                if ($this->haColonna('chat_threads', 'is_group')) {
                    $thread->is_group = true;
                    $thread->name = $nomeGruppo;
                }
                $thread->save();

                $attach = [$authUser->id => ['last_read_at' => now()]];
                foreach ($destinatari as $destinatario) {
                    $attach[$destinatario->id] = ['last_read_at' => null];
                }
                $thread->partecipanti()->attach($attach);

                return $thread;
            });

            return redirect()->action([self::class, 'index'], ['thread' => $thread->id]);
        }

        $destinatario = $destinatari->first();

        // Deduplica la creazione della DM: lock sulla coppia esistente in transazione
        $thread = DB::transaction(function () use ($authUser, $destinatario) {
            $esistente = $this->trovaThreadDueUtentiLock($authUser->id, $destinatario->id);
            if ($esistente) {
                return $esistente;
            }

            $thread = new ChatThread;
            $thread->created_by = $authUser->id;
            $thread->save();

            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $destinatario->id => ['last_read_at' => null],
            ]);

            return $thread;
        });

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

    public function attachment(Request $request, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $attachment = ChatMessageAttachment::query()->find($id);
        if (! $attachment) {
            // Record orfano / rimosso: niente 404 in console sulle anteprime (anche con cache CDN).
            if ($request->boolean('download')) {
                abort(404, 'Allegato non trovato');
            }

            return $this->attachmentMissingPlaceholderResponse();
        }

        /** @var User $authUser */
        $authUser = Auth::user();
        $messaggio = $attachment->messaggio;
        if (! $messaggio) {
            if ($request->boolean('download')) {
                abort(404);
            }

            return $this->attachmentMissingPlaceholderResponse();
        }

        $threadId = (int) $messaggio->thread_id;
        $this->ensureThreadAccesso($threadId, (int) $authUser->id);
        $this->ensureThreadConversazioneConsentita($threadId, $authUser);

        if ((bool) $attachment->is_blocked) {
            abort(403, 'Allegato non disponibile');
        }

        $relativePath = ltrim((string) $attachment->path_filename, '/');
        if ($relativePath === '') {
            if ($request->boolean('download')) {
                abort(404);
            }

            return $this->attachmentMissingPlaceholderResponse();
        }

        // I nuovi file vivono su disk `local`; i vecchi allegati potrebbero
        // essere ancora su `public`. Si prova prima local, poi public.
        $disk = $this->localizzaAllegato($relativePath);
        if (! $disk) {
            Log::warning('Chat attachment file missing on disk', [
                'attachment_id' => (int) $attachment->id,
                'message_id' => (int) $messaggio->id,
                'thread_id' => $threadId,
                'requested_by_user_id' => (int) $authUser->id,
                'relative_path' => $relativePath,
            ]);

            if ($request->boolean('download')) {
                abort(404, 'Allegato non trovato sul server');
            }

            return $this->attachmentMissingPlaceholderResponse();
        }

        $absolutePath = Storage::disk($disk)->path($relativePath);
        $mime = (string) $attachment->mime_type;
        $isSvg = Str::contains(Str::lower($mime), 'svg') || Str::endsWith(Str::lower($relativePath), '.svg');
        $isImmagine = Str::startsWith(Str::lower($mime), 'image/') && ! $isSvg;

        // Forza il download per SVG e per tutto ciò che non è immagine (anti-XSS inline).
        $download = $request->boolean('download') || ! $isImmagine;

        if ($download) {
            return response()->download($absolutePath, (string) $attachment->filename_originale);
        }

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="'.addslashes((string) $attachment->filename_originale).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function attachmentMissingPlaceholderResponse(): \Symfony\Component\HttpFoundation\Response
    {
        $placeholderPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        return response($placeholderPng ?: '', 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'CDN-Cache-Control' => 'no-store',
            'Cloudflare-CDN-Cache-Control' => 'no-store',
            'X-Chat-Attachment-Missing' => '1',
        ]);
    }

    public function sendMessage(Request $request, ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);
        $this->ensureThreadConversazioneConsentita($thread->id, $authUser);

        $request->validate([
            'messaggio' => ['nullable', 'string', 'max:3000'],
            'allegati' => ['nullable', 'array'],
            'allegati.*' => ['file', 'max:10240'],
            'reply_to_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $testoMessaggio = trim((string) $request->input('messaggio', ''));
        $allegati = array_values(array_filter($request->file('allegati', [])));

        if ($testoMessaggio === '' && count($allegati) === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Inserisci un messaggio o allega almeno un file.',
            ], 422);
        }

        // Il messaggio a cui si risponde deve appartenere allo stesso thread.
        $replyToId = null;
        if ($this->haReactionsTable() && $request->filled('reply_to_id')) {
            $replyToId = (int) $request->input('reply_to_id');
            $appartiene = ChatMessage::query()
                ->where('id', $replyToId)
                ->where('thread_id', $thread->id)
                ->exists();

            if (! $appartiene) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Il messaggio a cui rispondi non appartiene a questa conversazione.',
                ], 422);
            }
        }

        // Validazione allegati (allowlist estensione + MIME) prima di scrivere sul DB.
        foreach ($allegati as $allegato) {
            if (! $allegato) {
                continue;
            }

            if (! $this->allegatoConsentito($allegato)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Tipo file non consentito: '.$allegato->getClientOriginalName(),
                ], 422);
            }
        }

        $destinatariEmailPrimoNonLetto = $this->destinatariPrimoNonLetto($thread->id, (int) $authUser->id);

        // Persistenza atomica di messaggio + allegati.
        $messaggio = DB::transaction(function () use ($thread, $authUser, $testoMessaggio, $replyToId, $allegati, $request) {
            $messaggio = new ChatMessage;
            $messaggio->thread_id = $thread->id;
            $messaggio->user_id = $authUser->id;
            $messaggio->messaggio = $testoMessaggio;
            $messaggio->priority = $request->integer('priority', 0);
            if ($replyToId) {
                $messaggio->reply_to_id = $replyToId;
            }
            $messaggio->save();

            foreach ($allegati as $allegato) {
                if (! $allegato) {
                    continue;
                }

                // Nuovi allegati SOLO su disk `local` (storage/app/chat-allegati/...)
                $path = $allegato->store('chat-allegati', 'local');

                $recordAllegato = new ChatMessageAttachment;
                $recordAllegato->message_id = $messaggio->id;
                $recordAllegato->filename_originale = $allegato->getClientOriginalName();
                $recordAllegato->path_filename = $path;
                $recordAllegato->mime_type = $allegato->getClientMimeType();
                $recordAllegato->dimensione_file = $allegato->getSize();
                // Nessun antivirus: si dichiara "unchecked", non un falso "clean".
                $recordAllegato->scan_status = 'unchecked';
                $recordAllegato->scan_note = 'Nessuna scansione antivirus: consentito solo tramite allowlist estensione+MIME.';
                $recordAllegato->is_blocked = false;
                $recordAllegato->save();
            }

            return $messaggio;
        });

        $messaggio->load('mittente');

        $this->registraMenzioni($messaggio);

        $thread->touch();
        $this->segnaComeLetto($thread->id, $authUser->id);

        broadcast(new ChatMessageSent($messaggio))->toOthers();

        if (! empty($destinatariEmailPrimoNonLetto)) {
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

        $this->inviaPushDestinatari($thread, $messaggio, $authUser);

        return response()->json([
            'ok' => true,
            'message' => 'Messaggio inviato',
            'message_id' => (int) $messaggio->id,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        // Aggiorna stato online
        $this->aggiornaStatoOnline($authUser->id);

        $threads = $this->threadsPerUtente($authUser->id, $request->boolean('archived'));
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

        // Modalità delta: quando è presente after_id + delta=1 si restituiscono
        // solo i messaggi NUOVI (append) senza ri-renderizzare tutta la history.
        $afterId = $request->integer('after_id');
        $delta = $request->boolean('delta') && $afterId > 0;
        $ultimoId = $afterId;
        $hasNew = false;

        if ($threadAttivo) {
            $this->ensureThreadConversazioneConsentita($threadAttivo->id, $authUser);
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $this->segnaComeConsegnato($threadAttivo->id, $authUser->id);

            if ($delta) {
                $messaggi = $this->messaggiThreadDopo($threadAttivo->id, $afterId);
                $hasNew = $messaggi->isNotEmpty();
                $ultimoId = $messaggi->last()?->id ?? $afterId;
            } else {
                $messaggi = $this->messaggiThread($threadAttivo->id, null, 50);
                $ultimoId = $messaggi->last()?->id;
            }

            $activeLastMessageId = $delta
                ? ($ultimoId ?: $this->ultimoMessaggioIdThread($threadAttivo->id))
                : $messaggi->last()?->id;
            $activeLastMessageSenderId = $messaggi->last()?->user_id;
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

        // In delta si invia SOLO il frammento dei nuovi messaggi (da appendere);
        // altrimenti la history completa come prima.
        $messaggiHtml = ($delta && ! $hasNew)
            ? ''
            : view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
                'altroLastReadAt' => $altroLastReadAt,
            ])->render();

        return response()->json([
            'threadsHtml' => view('Backend.Chat._threads', [
                'threads' => $threads,
                'threadAttivo' => $threadAttivo,
                'onlineMap' => $onlineMap,
            ])->render(),
            'messaggiHtml' => $messaggiHtml,
            'delta' => $delta,
            'hasNew' => $hasNew,
            'ultimoId' => $ultimoId,
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
        $this->ensureThreadConversazioneConsentita($thread->id, $authUser);

        $typing = $request->boolean('typing');
        $key = $this->typingCacheKey($thread->id, $authUser->id);

        if ($typing) {
            Cache::put($key, $authUser->nominativo(), now()->addSeconds(8));
        } else {
            Cache::forget($key);
        }

        broadcast(new ChatTypingUpdated(
            (int) $thread->id,
            (int) $authUser->id,
            $authUser->nominativo(),
            $typing
        ))->toOthers();

        return response()->json(['ok' => true]);
    }

    /**
     * SSE: mantiene la connessione fino a ~20s controllando ogni 500ms i
     * messaggi con id > after_id. Appena ne trova, li invia e chiude.
     */
    public function stream(Request $request, ChatThread $thread): StreamedResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);
        $this->ensureThreadConversazioneConsentita($thread->id, $authUser);

        $afterId = (int) $request->integer('after_id');
        $threadId = (int) $thread->id;
        $escludiEliminati = $this->haColonna('chat_messages', 'deleted_at');

        // Evita che la sessione resti bloccata per tutta la durata dello stream.
        if ($request->hasSession()) {
            $request->session()->save();
        }

        $response = new StreamedResponse(function () use ($threadId, $afterId, $escludiEliminati) {
            @set_time_limit(0);
            @ignore_user_abort(true);

            $inizio = time();
            $lastId = $afterId;

            while (time() - $inizio < 20) {
                if (connection_aborted()) {
                    return;
                }

                $query = ChatMessage::query()
                    ->where('thread_id', $threadId)
                    ->where('id', '>', $lastId)
                    ->with('mittente:id,nome,cognome')
                    ->orderBy('id');

                if ($escludiEliminati) {
                    $query->whereNull('deleted_at');
                }

                $nuovi = $query->get();

                if ($nuovi->isNotEmpty()) {
                    $lastId = (int) $nuovi->last()->id;

                    $this->emettiEventoSse([
                        'hasNew' => true,
                        'thread_id' => $threadId,
                        'ultimoId' => $lastId,
                        'messages' => $nuovi->map(function (ChatMessage $m) {
                            return [
                                'id' => (int) $m->id,
                                'thread_id' => (int) $m->thread_id,
                                'user_id' => (int) $m->user_id,
                                'sender' => $m->mittente?->nominativo(),
                                'messaggio' => (string) $m->messaggio,
                                'created_at' => $m->created_at?->toIso8601String(),
                            ];
                        })->all(),
                    ]);

                    return;
                }

                usleep(500000);
            }

            // Timeout senza novità: il client riaprirà la connessione.
            $this->emettiEventoSse([
                'hasNew' => false,
                'ultimoId' => $lastId,
            ]);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    public function closeThread(ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        // Per i gruppi si limita a rimuovere l'utente dalla conversazione.
        $thread->partecipanti()->detach($authUser->id);

        if (! $thread->partecipanti()->exists()) {
            $thread->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Conversazione chiusa',
        ]);
    }

    /**
     * Archivia un thread (solo admin): resta nel DB ma è filtrato dalla lista
     * a meno di richiesta esplicita ?archived=1.
     */
    public function archiveThread(ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        abort_unless($authUser->hasPermissionTo('admin'), 403);

        if ($this->haColonna('chat_threads', 'archived_at')) {
            $thread->archived_at = $thread->archived_at ? null : now();
            $thread->save();
        }

        return response()->json([
            'ok' => true,
            'archived' => (bool) $thread->archived_at,
        ]);
    }

    /**
     * Cronologia audit (edit/delete) di un singolo messaggio.
     */
    public function messageHistory(ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);
        $this->ensureThreadConversazioneConsentita($message->thread_id, $authUser);

        if (! $this->haTabella('chat_message_audits')) {
            return response()->json(['history' => []]);
        }

        $history = ChatMessageAudit::query()
            ->where('message_id', $message->id)
            ->with('utente:id,nome,cognome')
            ->orderBy('id')
            ->get()
            ->map(function (ChatMessageAudit $audit) {
                return [
                    'id' => (int) $audit->id,
                    'azione' => (string) $audit->azione,
                    'old_text' => $audit->old_text,
                    'new_text' => $audit->new_text,
                    'utente' => $audit->utente?->nominativo() ?? 'Utente',
                    'created_at' => $audit->created_at?->toDateTimeString(),
                ];
            });

        return response()->json(['history' => $history]);
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

        $targetThread = ChatThread::findOrFail($targetThreadId);

        // Tutti i partecipanti del thread di destinazione devono essere ammessi.
        $this->ensureThreadConversazioneConsentita($targetThreadId, $authUser);

        $sorgente = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->whereIn('id', $request->input('message_ids', []))
            ->with('mittente:id,nome,cognome', 'allegati')
            ->orderBy('id')
            ->get();

        $haForwardedColumn = $this->haColonna('chat_messages', 'forwarded_from_id');
        $creati = [];

        DB::transaction(function () use ($sorgente, $targetThreadId, $authUser, $haForwardedColumn, &$creati) {
            foreach ($sorgente as $origine) {
                $testoMittente = $origine->mittente?->nominativo() ?? 'Utente';
                $contenuto = "[Inoltrato da {$testoMittente}]\n".(string) $origine->messaggio;

                $dati = [
                    'thread_id' => $targetThreadId,
                    'user_id' => $authUser->id,
                    'messaggio' => $contenuto,
                    'priority' => (int) $origine->priority,
                ];
                if ($haForwardedColumn) {
                    $dati['forwarded_from_id'] = $origine->id;
                }

                $nuovo = ChatMessage::query()->create($dati);

                // Copia degli allegati verso il nuovo messaggio.
                foreach ($origine->allegati as $allegato) {
                    $this->copiaAllegato($allegato, $nuovo->id);
                }

                $creati[] = $nuovo;
            }
        });

        $targetThread->touch();

        foreach ($creati as $nuovo) {
            $nuovo->load('mittente', 'allegati');
            broadcast(new ChatMessageSent($nuovo))->toOthers();
            $this->inviaPushDestinatari($targetThread, $nuovo, $authUser);
        }

        return response()->json(['ok' => true, 'count' => count($creati)]);
    }

    public function quickTemplates(): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        return response()->json([
            'templates' => $this->quickTemplatesData($authUser->id),
        ]);
    }

    public function saveQuickTemplate(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $request->validate([
            'titolo' => ['required', 'string', 'max:120'],
            'contenuto' => ['required', 'string', 'max:3000'],
            'is_global' => ['nullable', 'boolean'],
        ]);

        // Solo gli admin possono creare template globali.
        $isGlobal = $request->boolean('is_global') && $authUser->hasPermissionTo('admin');

        $template = ChatQuickTemplate::query()->create([
            'user_id' => $authUser->id,
            'titolo' => $request->input('titolo'),
            'contenuto' => $request->input('contenuto'),
            'is_global' => $isGlobal,
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
            'configured' => ! empty(config('services.webpush.vapid.public_key')),
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

        if (! $destinatario) {
            return response()->json([
                'ok' => false,
                'message' => 'Utente menzionato non trovato o non disponibile',
            ], 404);
        }

        // Deduplica la DM con lock in transazione (evita coppie doppie in race).
        $thread = DB::transaction(function () use ($authUser, $destinatario) {
            $esistente = $this->trovaThreadDueUtentiLock($authUser->id, $destinatario->id);
            if ($esistente) {
                return $esistente;
            }

            $thread = new ChatThread;
            $thread->created_by = $authUser->id;
            $thread->save();

            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $destinatario->id => ['last_read_at' => null],
            ]);

            return $thread;
        });

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
        if (! $isAgenteSupervisorePair) {
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

    /**
     * Variante con lockForUpdate della ricerca coppia 1:1: da usare dentro una
     * transazione per deduplicare la creazione della DM in condizioni di race.
     */
    protected function trovaThreadDueUtentiLock(int $utenteA, int $utenteB): ?ChatThread
    {
        $query = ChatThread::query()
            ->whereHas('partecipanti', function ($query) use ($utenteA) {
                $query->where('users.id', $utenteA);
            })
            ->whereHas('partecipanti', function ($query) use ($utenteB) {
                $query->where('users.id', $utenteB);
            })
            ->whereDoesntHave('partecipanti', function ($query) use ($utenteA, $utenteB) {
                $query->whereNotIn('users.id', [$utenteA, $utenteB]);
            });

        // Esclude i gruppi dalle coppie 1:1 quando la colonna è disponibile.
        if ($this->haColonna('chat_threads', 'is_group')) {
            $query->where(function ($q) {
                $q->where('is_group', false)->orWhereNull('is_group');
            });
        }

        $threadId = $query->latest('id')
            ->lockForUpdate()
            ->value('chat_threads.id');

        return $threadId ? ChatThread::find($threadId) : null;
    }

    /**
     * Allowlist estensione + MIME per gli allegati chat.
     */
    protected function allegatoConsentito($allegato): bool
    {
        if (! $allegato) {
            return false;
        }

        $estensioniConsentite = [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip',
        ];

        $mimeConsentiti = [
            'application/pdf',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv', 'application/csv',
            'application/zip', 'application/x-zip-compressed', 'multipart/x-zip',
        ];

        $ext = Str::lower((string) $allegato->getClientOriginalExtension());
        $mime = Str::lower((string) $allegato->getClientMimeType());

        return in_array($ext, $estensioniConsentite, true)
            && in_array($mime, $mimeConsentiti, true);
    }

    /**
     * Individua su quale disk (`local` o `public`) risiede l'allegato.
     */
    protected function localizzaAllegato(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        if (Storage::disk('local')->exists($relativePath)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($relativePath)) {
            return 'public';
        }

        return null;
    }

    /**
     * Copia il file di un allegato verso un nuovo messaggio (inoltro).
     * Le nuove scritture vanno sempre su disk `local`.
     */
    protected function copiaAllegato(ChatMessageAttachment $origine, int $nuovoMessaggioId): void
    {
        $relativeOrigine = ltrim((string) $origine->path_filename, '/');
        $disk = $this->localizzaAllegato($relativeOrigine);

        $record = new ChatMessageAttachment;
        $record->message_id = $nuovoMessaggioId;
        $record->filename_originale = $origine->filename_originale;
        $record->mime_type = $origine->mime_type;
        $record->dimensione_file = $origine->dimensione_file;
        $record->scan_status = $origine->scan_status ?: 'unchecked';
        $record->scan_note = $origine->scan_note;
        $record->is_blocked = (bool) $origine->is_blocked;

        if ($disk) {
            $estensione = pathinfo($relativeOrigine, PATHINFO_EXTENSION);
            $nuovoRelative = 'chat-allegati/'.Str::random(40).($estensione ? '.'.$estensione : '');
            $contenuto = Storage::disk($disk)->get($relativeOrigine);
            Storage::disk('local')->put($nuovoRelative, $contenuto);
            $record->path_filename = $nuovoRelative;
        } else {
            // File sorgente mancante: si mantiene il riferimento originale.
            $record->path_filename = $relativeOrigine;
        }

        $record->save();
    }

    /**
     * Elenca i destinatari per i quali questo è il primo messaggio non letto
     * (per l'invio della mail di cortesia). Esclude i messaggi eliminati.
     */
    protected function destinatariPrimoNonLetto(int $threadId, int $mittenteId): array
    {
        $risultato = [];
        $escludiEliminati = $this->haColonna('chat_messages', 'deleted_at');

        $partecipazioni = ChatThreadUser::query()
            ->where('thread_id', $threadId)
            ->where('user_id', '<>', $mittenteId)
            ->get(['user_id', 'last_read_at']);

        foreach ($partecipazioni as $partecipazione) {
            $query = ChatMessage::query()
                ->where('thread_id', $threadId)
                ->where('user_id', '<>', (int) $partecipazione->user_id);

            if ($escludiEliminati) {
                $query->whereNull('deleted_at');
            }

            if ($partecipazione->last_read_at) {
                $query->where('created_at', '>', $partecipazione->last_read_at);
            }

            if (! $query->exists()) {
                $risultato[] = (int) $partecipazione->user_id;
            }
        }

        return $risultato;
    }

    /**
     * Dispatch delle notifiche web push a tutti i destinatari del thread.
     */
    protected function inviaPushDestinatari(ChatThread $thread, ChatMessage $messaggio, User $mittente): void
    {
        $destinatariPush = ChatThreadUser::query()
            ->where('thread_id', $thread->id)
            ->where('user_id', '<>', $mittente->id)
            ->pluck('user_id');

        $threadName = $thread->is_group
            ? ($thread->name ?: 'Gruppo')
            : ($mittente->nominativo() ?: 'Chat interna');

        foreach ($destinatariPush as $destinatarioId) {
            $destinatarioId = (int) $destinatarioId;
            if ($this->threadSilenziatoPerUtente($thread->id, $destinatarioId)) {
                continue;
            }

            SendChatWebPushNotification::dispatch($destinatarioId, [
                'title' => $mittente->nominativo().' · Chat interna',
                'body' => Str::limit(strip_tags((string) ($messaggio->messaggio ?: '📎 Nuovo allegato in chat')), 120),
                'url' => url('/backend/chat-interna?thread='.$thread->id),
                'thread_id' => (int) $thread->id,
                'message_id' => (int) $messaggio->id,
                'tag' => 'chat-thread-'.$thread->id,
                'icon' => url('/images/logo_small_icon_only.png'),
                'badge' => url('/images/logo_small_icon_only.png'),
                'thread_name' => $threadName,
            ]);
        }
    }

    /**
     * Verifica se l'indice fulltext sui messaggi è disponibile (solo MySQL/MariaDB).
     */
    protected function fulltextDisponibile(): bool
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return $cache = false;
        }

        try {
            $cache = DB::table('information_schema.statistics')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('table_name', 'chat_messages')
                ->where('index_name', 'chat_messages_messaggio_fulltext')
                ->exists();
        } catch (\Throwable $e) {
            $cache = false;
        }

        return $cache;
    }

    /**
     * Prepara il termine per la ricerca fulltext in BOOLEAN MODE (prefix match).
     */
    protected function preparaTermineFulltext(string $q): string
    {
        $parole = preg_split('/\s+/', trim($q)) ?: [];

        $termini = collect($parole)
            ->filter(fn ($p) => mb_strlen($p) >= 2)
            ->map(function ($parola) {
                $pulita = preg_replace('/[+\-><\(\)~*\"@]+/', '', $parola);

                return $pulita !== '' ? '+'.$pulita.'*' : '';
            })
            ->filter()
            ->implode(' ');

        return $termini !== '' ? $termini : $q;
    }

    /**
     * Evidenzia il termine cercato nel testo (wrap in <mark>), HTML-escaped.
     */
    protected function evidenziaTermine(string $testo, string $q): string
    {
        $escaped = e($testo);
        $q = trim($q);

        if ($q === '') {
            return $escaped;
        }

        return preg_replace_callback(
            '/'.preg_quote(e($q), '/').'/iu',
            fn ($m) => '<mark>'.$m[0].'</mark>',
            $escaped
        ) ?? $escaped;
    }

    /**
     * Emette un evento SSE (formato `data: {json}\n\n`) con flush del buffer.
     */
    protected function emettiEventoSse(array $payload): void
    {
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }

    protected function threadsPerUtente(int $userId, bool $includeArchived = false)
    {
        $utenteCorrente = User::query()->find($userId, ['id', 'nome', 'cognome']);

        $haMute = $this->haColonna('chat_thread_users', 'muted_until');
        $haArchiviazione = $this->haColonna('chat_threads', 'archived_at');
        $escludiEliminati = $this->haColonna('chat_messages', 'deleted_at');

        $query = ChatThread::query()
            ->select('chat_threads.*')
            ->join('chat_thread_users as mia_partecipazione', function ($join) use ($userId) {
                $join->on('mia_partecipazione.thread_id', '=', 'chat_threads.id')
                    ->where('mia_partecipazione.user_id', '=', $userId);
            })
            ->with(['partecipanti:id,nome,cognome', 'ultimoMessaggio.mittente:id,nome,cognome'])
            // N+1 mute risolto: si porta muted_until direttamente dal join.
            ->when($haMute, fn ($q) => $q->addSelect('mia_partecipazione.muted_until as mia_muted_until'))
            ->selectSub(function ($query) use ($userId, $escludiEliminati) {
                $query->from('chat_messages')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('chat_messages.thread_id', 'chat_threads.id')
                    ->where('chat_messages.user_id', '<>', $userId)
                    ->whereRaw("chat_messages.created_at > COALESCE(mia_partecipazione.last_read_at, '1970-01-01 00:00:00')");

                if ($escludiEliminati) {
                    $query->whereNull('chat_messages.deleted_at');
                }
            }, 'unread_count')
            ->orderByDesc(DB::raw('COALESCE((SELECT MAX(cm.created_at) FROM chat_messages cm WHERE cm.thread_id = chat_threads.id), chat_threads.created_at)'));

        // I thread archiviati sono nascosti salvo richiesta esplicita.
        if ($haArchiviazione && ! $includeArchived) {
            $query->whereNull('chat_threads.archived_at');
        }

        $threads = $query->get();

        $threads->each(function (ChatThread $thread) use ($userId, $utenteCorrente, $haMute) {
            if ($haMute) {
                $mutedUntil = $thread->mia_muted_until;
                $thread->is_muted = $mutedUntil ? now()->lt($mutedUntil) : false;
            }

            $altro = $thread->partecipanti->firstWhere('id', '!=', $userId);
            $thread->setRelation('altroPartecipante', $altro);

            if ($thread->is_group) {
                // Nei gruppi: chat consentita solo se posso parlare con TUTTI gli altri.
                $altri = $thread->partecipanti->where('id', '!=', $userId);
                $thread->can_chat = $utenteCorrente
                    ? $altri->every(fn (User $u) => $this->puoConversare($utenteCorrente, $u))
                    : false;
                $thread->display_name = $thread->nomeVisualizzato();
            } elseif ($utenteCorrente && $altro instanceof User) {
                $thread->can_chat = $this->puoConversare($utenteCorrente, $altro);
                $thread->display_name = $altro->nominativo();
            } else {
                $thread->can_chat = false;
                $thread->display_name = $thread->name;
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
            $typingName = Cache::get($this->typingCacheKey($threadId, (int) $participantId));
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
        return 'chat_typing_'.$threadId.'_'.$userId;
    }

    /* ------------------------------------------------------------------ */
    /*  REAZIONE EMOJI */
    /* ------------------------------------------------------------------ */

    public function toggleReaction(Request $request, ChatMessage $message): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($message->thread_id, $authUser->id);

        if (! $this->haReactionsTable()) {
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
    /*  RICERCA MESSAGGI */
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

        // Esclude i messaggi eliminati (soft delete) quando la colonna esiste.
        if ($this->haColonna('chat_messages', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $usaFulltext = false;
        if ($q !== '') {
            if ($this->fulltextDisponibile()) {
                // MATCH ... AGAINST in BOOLEAN MODE (prefix match sui termini)
                $termine = $this->preparaTermineFulltext($q);
                $query->whereRaw('MATCH(messaggio) AGAINST (? IN BOOLEAN MODE)', [$termine]);
                $usaFulltext = true;
            } else {
                $query->where('messaggio', 'LIKE', '%'.$q.'%');
            }
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

        // Se il fulltext fallisce (es. indice assente) si ricade sul LIKE.
        try {
            $collezione = $query->get();
        } catch (\Throwable $e) {
            $collezione = ChatMessage::query()
                ->whereIn('thread_id', $threadIds)
                ->when($this->haColonna('chat_messages', 'deleted_at'), fn ($b) => $b->whereNull('deleted_at'))
                ->when($q !== '', fn ($b) => $b->where('messaggio', 'LIKE', '%'.$q.'%'))
                ->with('mittente:id,nome,cognome')
                ->latest('id')
                ->limit(50)
                ->get();
            $usaFulltext = false;
        }

        $results = $collezione->map(function (ChatMessage $msg) use ($q) {
            return [
                'id' => $msg->id,
                'thread_id' => $msg->thread_id,
                'mittente' => $msg->mittente?->nominativo() ?? 'Utente',
                'messaggio' => $msg->messaggio,
                'highlight' => $this->evidenziaTermine((string) $msg->messaggio, $q),
                'data' => $msg->created_at?->format('d/m/Y H:i'),
                'priority' => (int) ($msg->priority ?? 0),
            ];
        });

        return response()->json([
            'risultati' => $results,
            'fulltext' => $usaFulltext,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  ONLINE STATUS */
    /* ------------------------------------------------------------------ */

    protected function aggiornaStatoOnline(int $userId): void
    {
        Cache::put('chat_online_'.$userId, true, now()->addSeconds(60));
    }

    protected function isOnline(int $userId): bool
    {
        return (bool) Cache::get('chat_online_'.$userId, false);
    }

    /* ------------------------------------------------------------------ */
    /*  READ RECEIPT HELPER */
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

        // Carica reazioni (con autore, per evitare N+1) e reply se la migrazione esiste
        if ($this->haReactionsTable()) {
            $query->with('reazioni.utente')
                ->with('replyTo.mittente:id,nome,cognome');
        }

        return $query->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Solo i messaggi con id > afterId, in ordine cronologico (per l'append delta/SSE).
     */
    protected function messaggiThreadDopo(int $threadId, int $afterId)
    {
        $query = ChatMessage::query()
            ->where('thread_id', $threadId)
            ->where('id', '>', $afterId)
            ->with('mittente:id,nome,cognome')
            ->with('allegati');

        if ($this->haTabella('chat_message_pins')) {
            $query->with('pin');
        }

        if ($this->haTabella('chat_message_favorites')) {
            $query->with('preferiti');
        }

        if ($this->haColonna('chat_messages', 'forwarded_from_id')) {
            $query->with('inoltratoDa.mittente:id,nome,cognome');
        }

        if ($this->haReactionsTable()) {
            $query->with('reazioni.utente')
                ->with('replyTo.mittente:id,nome,cognome');
        }

        return $query->orderBy('id')
            ->limit(200)
            ->get()
            ->values();
    }

    protected function ultimoMessaggioIdThread(int $threadId): ?int
    {
        return ChatMessage::query()
            ->where('thread_id', $threadId)
            ->max('id');
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

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table);
        }

        return (bool) $cache[$table];
    }

    protected function haColonna(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table.'.'.$column;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return (bool) $cache[$key];
    }

    protected function segnaComeConsegnato(int $threadId, int $currentUserId): void
    {
        if (! $this->haColonna('chat_messages', 'delivered_at')) {
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
        if (! $this->haColonna('chat_thread_users', 'muted_until')) {
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
        if (! $this->haTabella('chat_quick_templates')) {
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
        if (! $this->haTabella('chat_message_pins')) {
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
            ->when($this->haColonna('chat_messages', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->latest('id')
            ->with('mittente:id,nome,cognome')
            ->first();

        if (! $message) {
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
        if (! $this->haTabella('chat_message_mentions')) {
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

            if (! $this->threadSilenziatoPerUtente($messaggio->thread_id, (int) $utenteMenzionato->id)) {
                $utenteMenzionato->notify(new NotificaPrimoMessaggioChatInterna($messaggio));
            }
        }
    }
}

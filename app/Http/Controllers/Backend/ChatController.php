<?php

namespace App\Http\Controllers\Backend;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\ChatMessageReaction;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureRuoloConsentito($authUser);

        $threads = $this->threadsPerUtente($authUser->id);

        $threadId = $request->integer('thread');
        $threadAttivo = null;

        if ($threadId) {
            $threadAttivo = $threads->firstWhere('id', $threadId);
        }

        if (!$threadAttivo) {
            $threadAttivo = $threads->first();
        }

        $messaggi = collect();
        if ($threadAttivo) {
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $messaggi = $this->messaggiThread($threadAttivo->id);
        }

        // Timestamp di lettura dell'altro partecipante (per le conferme di lettura)
        $altroLastReadAt = null;
        if ($threadAttivo) {
            $altroLastReadAt = $this->altroLastReadAt($threadAttivo->id, $authUser->id);
        }

        // Aggiorna stato online dell'utente corrente
        $this->aggiornaStatoOnline($authUser->id);

        return view('Backend.Chat.index', [
            'controller' => get_class($this),
            'titoloPagina' => 'Chat interna',
            'threads' => $threads,
            'threadAttivo' => $threadAttivo,
            'messaggi' => $messaggi,
            'utentiDisponibili' => $this->utentiDisponibili($authUser),
            'altroLastReadAt' => $altroLastReadAt,
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

        $this->segnaComeLetto($thread->id, $authUser->id);
        $messaggi = $this->messaggiThread($thread->id);
        $altroLastReadAt = $this->altroLastReadAt($thread->id, $authUser->id);

        return response()->json([
            'html' => view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
                'altroLastReadAt' => $altroLastReadAt,
            ])->render(),
            'ultimoId' => $messaggi->last()?->id,
        ]);
    }

    public function sendMessage(Request $request, ChatThread $thread): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $this->ensureThreadAccesso($thread->id, $authUser->id);

        $request->validate([
            'messaggio' => ['nullable', 'string', 'max:3000', 'required_without:allegati'],
            'allegati' => ['nullable', 'array'],
            'allegati.*' => ['file', 'max:10240'],
            'reply_to_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
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

        $messaggio = new ChatMessage();
        $messaggio->thread_id = $thread->id;
        $messaggio->user_id = $authUser->id;
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->reply_to_id = $request->input('reply_to_id');
        $messaggio->save();
        $messaggio->load('mittente');

        $allegati = $request->file('allegati', []);
        foreach ($allegati as $allegato) {
            if (!$allegato) {
                continue;
            }

            $path = $allegato->store('chat-allegati', 'public');

            $recordAllegato = new ChatMessageAttachment();
            $recordAllegato->message_id = $messaggio->id;
            $recordAllegato->filename_originale = $allegato->getClientOriginalName();
            $recordAllegato->path_filename = $path;
            $recordAllegato->mime_type = $allegato->getClientMimeType();
            $recordAllegato->dimensione_file = $allegato->getSize();
            $recordAllegato->save();
        }

        $thread->touch();
        $this->segnaComeLetto($thread->id, $authUser->id);

        broadcast(new ChatMessageSent($messaggio))->toOthers();

        if (!empty($destinatariEmailPrimoNonLetto)) {
            User::query()
                ->whereIn('id', $destinatariEmailPrimoNonLetto)
                ->get()
                ->each(function (User $destinatario) use ($messaggio) {
                    $destinatario->notify(new NotificaPrimoMessaggioChatInterna($messaggio));
                });
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

        if ($threadAttivo) {
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $messaggi = $this->messaggiThread($threadAttivo->id);
            $typingStatus = $this->typingStatus($threadAttivo->id, $authUser->id);
            $altroLastReadAt = $this->altroLastReadAt($threadAttivo->id, $authUser->id);

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
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'admin');
            })
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cognome']);
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

        return ($aAdmin && $bOperativo) || ($bAdmin && $aOperativo);
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

        $threads->each(function (ChatThread $thread) use ($userId) {
            $altro = $thread->partecipanti->firstWhere('id', '!=', $userId);
            $thread->setRelation('altroPartecipante', $altro);
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
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'thread_id' => ['nullable', 'integer'],
        ]);

        $q = $request->input('q');
        $threadId = $request->integer('thread_id');

        // Cerca solo nei thread dell'utente
        $threadIds = ChatThreadUser::query()
            ->where('user_id', $authUser->id)
            ->pluck('thread_id');

        $query = ChatMessage::query()
            ->whereIn('thread_id', $threadIds)
            ->where('messaggio', 'LIKE', '%' . $q . '%')
            ->with('mittente:id,nome,cognome')
            ->latest('id')
            ->limit(50);

        if ($threadId) {
            $query->where('thread_id', $threadId);
        }

        $results = $query->get()->map(function (ChatMessage $msg) {
            return [
                'id' => $msg->id,
                'thread_id' => $msg->thread_id,
                'mittente' => $msg->mittente?->nominativo() ?? 'Utente',
                'messaggio' => $msg->messaggio,
                'data' => $msg->created_at?->format('d/m/Y H:i'),
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

    protected function messaggiThread(int $threadId)
    {
        return ChatMessage::query()
            ->where('thread_id', $threadId)
            ->with('mittente:id,nome,cognome')
            ->with('allegati')
            ->with('reazioni')
            ->with('replyTo.mittente:id,nome,cognome')
            ->latest('id')
            ->limit(200)
            ->get()
            ->reverse()
            ->values();
    }
}

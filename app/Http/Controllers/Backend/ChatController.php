<?php

namespace App\Http\Controllers\Backend;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('Backend.Chat.index', [
            'controller' => get_class($this),
            'titoloPagina' => 'Chat interna',
            'threads' => $threads,
            'threadAttivo' => $threadAttivo,
            'messaggi' => $messaggi,
            'utentiDisponibili' => $this->utentiDisponibili($authUser),
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

        return response()->json([
            'html' => view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
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
            'messaggio' => ['required', 'string', 'max:3000'],
        ]);

        $messaggio = new ChatMessage();
        $messaggio->thread_id = $thread->id;
        $messaggio->user_id = $authUser->id;
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->save();

        $thread->touch();
        $this->segnaComeLetto($thread->id, $authUser->id);

        broadcast(new ChatMessageSent($messaggio->load('mittente')))->toOthers();

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

        $threads = $this->threadsPerUtente($authUser->id);
        $threadAttivo = null;
        $messaggi = collect();

        $threadId = $request->integer('thread_id');
        if ($threadId) {
            $threadAttivo = $threads->firstWhere('id', $threadId);
        }

        if ($threadAttivo) {
            $this->segnaComeLetto($threadAttivo->id, $authUser->id);
            $messaggi = $this->messaggiThread($threadAttivo->id);
        }

        return response()->json([
            'threadsHtml' => view('Backend.Chat._threads', [
                'threads' => $threads,
                'threadAttivo' => $threadAttivo,
            ])->render(),
            'messaggiHtml' => view('Backend.Chat._messages', [
                'messaggi' => $messaggi,
            ])->render(),
            'nonLettiTotali' => ChatThreadUser::conteggioNonLetti($authUser->id),
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

    protected function messaggiThread(int $threadId)
    {
        return ChatMessage::query()
            ->where('thread_id', $threadId)
            ->with('mittente:id,nome,cognome')
            ->latest('id')
            ->limit(200)
            ->get()
            ->reverse()
            ->values();
    }
}

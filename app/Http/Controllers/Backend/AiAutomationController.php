<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\AiConversation;
use App\Models\AiEvent;
use App\Models\AiSuggestion;
use App\Models\User;
use App\Services\AiAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiAutomationController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->hasPermissionTo('admin');

        $suggestions = AiSuggestion::query()
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->orderByRaw("FIELD(priority, 'critica', 'alta', 'media', 'bassa')")
            ->orderByDesc('id')
            ->paginate(20);

        $events = AiEvent::query()
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('Backend.AiAutomation.index', [
            'titoloPagina' => 'Gestiio AI',
            'mainMenu' => 'ai-automation',
            'suggestions' => $suggestions,
            'events' => $events,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function triggerDashboard(Request $request, AiAutomationService $automation)
    {
        /** @var User $user */
        $user = Auth::user();
        $audience = $user->hasPermissionTo('admin') ? 'admin' : 'agente';

        $event = $automation->dispatch('dashboard_review', [
            'source' => 'manual_trigger',
            'filters' => $request->only(['periodo', 'priorita', 'stato', 'cliente']),
        ], $user, null, $audience);

        return back()->with('success', 'Analisi avviata. Il nuovo consiglio arriverà appena pronto.');
    }

    public function feedback(Request $request, AiSuggestion $suggestion)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_if(! $user->hasPermissionTo('admin') && (int) $suggestion->user_id !== (int) $user->id, 403);

        $data = $request->validate([
            'azione' => ['required', 'in:seen,accepted,dismissed,clicked'],
            'payload' => ['nullable', 'array'],
        ]);

        if ($data['azione'] === 'seen' && ! $suggestion->seen_at) {
            $suggestion->seen_at = now();
        }

        if ($data['azione'] === 'accepted') {
            $suggestion->status = 'accepted';
            $suggestion->accepted_at = now();
        }

        if ($data['azione'] === 'dismissed') {
            $suggestion->status = 'dismissed';
            $suggestion->dismissed_at = now();
        }

        $suggestion->save();

        AiAction::create([
            'ai_suggestion_id' => $suggestion->id,
            'user_id' => $user->id,
            'action_type' => $data['azione'],
            'status' => 'logged',
            'payload' => $data['payload'] ?? [],
        ]);

        return back();
    }

    public function ask(Request $request, AiAutomationService $automation)
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'scope' => ['nullable', 'string', 'max:80'],
        ]);

        $audience = $user->hasPermissionTo('admin') ? 'admin' : 'agente';

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'audience' => $audience,
            'scope' => $data['scope'] ?? 'dashboard',
            'prompt' => $data['prompt'],
            'answer' => 'Richiesta inviata. Il consiglio apparirà in Gestiio AI appena l’analisi sarà pronta.',
            'metadata' => [
                'source' => 'dashboard_prompt',
            ],
        ]);

        $automation->dispatch('agent_question', [
            'prompt' => $data['prompt'],
            'conversation_id' => $conversation->id,
            'scope' => $conversation->scope,
        ], $user, null, $audience);

        return back()->with('success', 'Domanda inviata all’assistente AI.');
    }

    public function chat(Request $request, AiAutomationService $automation)
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'array'],
            'conversation_id' => ['nullable', 'integer'],
            'history' => ['nullable', 'array'],
        ]);

        $context = $data['context'] ?? [];
        $audience = $user->hasPermissionTo('admin') ? 'admin' : 'agente';
        $area = $this->resolveChatArea((string) ($context['url'] ?? ''));
        $memory = $this->conversationMemory($user, $data['conversation_id'] ?? null, $data['history'] ?? []);
        $memory['workflow'] = $context['workflow'] ?? ($memory['workflow'] ?? null);
        $isRechargeRequest = $this->isRechargeRequest($data['prompt']);
        $answer = $this->buildChatAnswer($data['prompt'], $area, $audience, $memory);
        $actions = $this->chatActions($area, $data['prompt'], $memory);

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'audience' => $audience,
            'scope' => $area['key'],
            'subject_type' => $memory['session_id'] ? AiConversation::class : null,
            'subject_id' => $memory['session_id'],
            'prompt' => $data['prompt'],
            'answer' => $answer,
            'metadata' => [
                'source' => 'global_chat',
                'context' => $context,
                'actions' => $actions,
                'intent' => $this->chatIntent($data['prompt'], $memory),
                'workflow' => $isRechargeRequest ? 'recharge_plafond' : ($memory['workflow'] ?? null),
            ],
        ]);

        $sessionId = $memory['session_id'] ?: $conversation->id;

        $automation->dispatch('global_chat_message', [
            'prompt' => $data['prompt'],
            'conversation_id' => $conversation->id,
            'session_id' => $sessionId,
            'area' => $area,
            'context' => $context,
            'history' => $memory['history'],
            'expected_output' => 'Rispondi in italiano, in modo breve. Se serve una modifica o un controllo, proponi la prossima azione concreta.',
        ], $user, null, $audience);

        return response()->json([
            'conversation_id' => $sessionId,
            'answer' => $answer,
            'actions' => $actions,
            'workflow' => $isRechargeRequest ? 'recharge_plafond' : null,
        ]);
    }

    protected function resolveChatArea(string $path): array
    {
        $areas = [
            'contratto-energia' => ['label' => 'Contratti energia', 'url' => url('/backend/contratto-energia'), 'verb' => 'controllare stati, allegati e prossime scadenze dei contratti energia'],
            'contratto' => ['label' => 'Contratti telefonia', 'url' => url('/backend/contratto'), 'verb' => 'controllare stati, allegati e prossime scadenze dei contratti telefonia'],
            'caf-patronato' => ['label' => 'CAF / Patronato', 'url' => url('/backend/caf-patronato'), 'verb' => 'trovare pratiche ferme, documenti mancanti e attività da chiudere'],
            'visura' => ['label' => 'Visure', 'url' => url('/backend/visura'), 'verb' => 'seguire richieste visura, allegati e pratiche in attesa'],
            'spedizione-brt' => ['label' => 'Spedizione BRT', 'url' => url('/backend/spedizione-brt'), 'verb' => 'controllare spedizioni, tracking e preparazione colli'],
            'spedizione-inpost' => ['label' => 'Spedizione InPost', 'url' => url('/backend/spedizione-inpost'), 'verb' => 'controllare spedizioni InPost, locker e tracking'],
            'cliente-assistenza' => ['label' => 'Clienti assistenza', 'url' => url('/backend/cliente-assistenza'), 'verb' => 'cercare clienti assistenza e verificare contatti mancanti'],
            'richiesta-assistenza' => ['label' => 'Richieste assistenza', 'url' => url('/backend/richiesta-assistenza'), 'verb' => 'classificare richieste SPID, recuperi e assistenze da contabilizzare'],
            'cliente' => ['label' => 'Clienti', 'url' => url('/backend/cliente'), 'verb' => 'cercare clienti e leggere dati utili prima di aprire una pratica'],
            'agente' => ['label' => 'Agenti', 'url' => url('/backend/agente'), 'verb' => 'controllare produzione, plafond e attività degli agenti'],
            'documenti' => ['label' => 'Documenti', 'url' => url('/backend/documenti'), 'verb' => 'trovare documenti, cartelle e file mancanti'],
            'ricarica-carta-iban' => ['label' => 'IBAN ricariche carte', 'url' => url('/backend/ricarica-carta-iban'), 'verb' => 'controllare IBAN e dati di ricarica carte'],
            'carica-plafond' => ['label' => 'Ricarica plafond agenti', 'url' => url('/backend/carica-plafond'), 'verb' => 'aiutare con ricariche plafond e controlli sui movimenti'],
            'chat-interna' => ['label' => 'Chat interna', 'url' => url('/backend/chat-interna'), 'verb' => 'preparare risposte e recuperare conversazioni interne'],
            'ticket' => ['label' => 'Ticket', 'url' => url('/backend/ticket'), 'verb' => 'ordinare ticket, priorità, messaggi e assegnazioni'],
            'fattura-proforma' => ['label' => 'Proforma', 'url' => url('/backend/fattura-proforma'), 'verb' => 'controllare proforma e righe di fatturazione'],
            'settings' => ['label' => 'Impostazioni', 'url' => url('/backend/settings'), 'verb' => 'trovare impostazioni e capire cosa modificare'],
            'registro' => ['label' => 'Registri', 'url' => url('/backend/registro/login'), 'verb' => 'leggere registri, log e controlli di sistema'],
            'lavoro' => ['label' => 'Dashboard', 'url' => url('/backend/lavoro'), 'verb' => 'scegliere cosa controllare adesso'],
        ];

        foreach ($areas as $key => $area) {
            if (Str::contains($path, $key)) {
                return array_merge(['key' => $key], $area);
            }
        }

        return array_merge(['key' => 'backend'], [
            'label' => 'Backend',
            'url' => url('/backend/lavoro'),
            'verb' => 'capire dove intervenire e aprire la sezione giusta',
        ]);
    }

    protected function buildChatAnswer(string $prompt, array $area, string $audience, array $memory = []): string
    {
        $lower = Str::lower($prompt);
        $intent = $this->chatIntent($prompt, $memory);

        if ($this->isRechargeRequest($prompt)) {
            return 'Ti seguo io per la ricarica. Scegli importo e portafoglio, poi inserisci la carta: il plafond verrà aggiornato appena Stripe conferma il pagamento.';
        }

        if ($intent === 'cancel') {
            return 'Ok, mi fermo qui. Quando vuoi ripartire dimmi cosa vuoi fare e riprendo dal punto giusto.';
        }

        if ($intent === 'confirm' && ($memory['last_intent'] ?? null)) {
            return $this->continuePreviousAnswer($area, $memory);
        }

        if (($memory['workflow'] ?? null) === 'recharge_plafond' && Str::contains($lower, ['20', '50', '100', 'servizi', 'spedizioni', 'visure'])) {
            return 'Perfetto. Usa il modulo di ricarica che ho aperto qui in chat: seleziona quel valore, scegli il portafoglio e conferma con la carta.';
        }

        if (Str::contains($lower, ['messaggio', 'scrivi', 'risposta cliente'])) {
            return 'Ti preparo una bozza pulita. Dimmi solo il nome del cliente e cosa dobbiamo chiedere o comunicare.';
        }

        if (Str::contains($lower, ['crea', 'apri', 'nuovo', 'nuova'])) {
            return 'Posso aiutarti a creare o aprire il record giusto. Parti dalla sezione '.$area['label'].' e dimmi quali dati hai già.';
        }

        if (Str::contains($lower, ['priorità', 'priorita', 'prossima', 'controllare'])) {
            return 'Qui guarderei prima elementi fermi, dati mancanti e pratiche con scadenza vicina. In '.$area['label'].' posso aiutarti a '.$area['verb'].'.';
        }

        if ($audience === 'admin') {
            return 'Ho letto il contesto della pagina. In '.$area['label'].' posso aiutarti a '.$area['verb'].' o preparare un controllo operativo.';
        }

        if (! empty($memory['last_prompt']) && Str::length($prompt) < 18) {
            return 'Ti seguo. Mi riferisco a quello che stavamo facendo: '.$memory['last_prompt'].'. Dimmi il dato che vuoi aggiungere oppure scrivi "annulla" per cambiare richiesta.';
        }

        return 'Sono su '.$area['label'].'. Posso aiutarti a '.$area['verb'].'. Dimmi l’obiettivo preciso e resto su questo filo finché non lo chiudiamo.';
    }

    protected function chatActions(array $area, string $prompt, array $memory = []): array
    {
        $actions = [];

        $lower = Str::lower($prompt);

        if ($this->isRechargeRequest($prompt)) {
            return $actions;
        }

        $actions[] = ['label' => 'Apri '.$area['label'], 'url' => $area['url']];

        if (Str::contains($lower, ['ticket', 'problema', 'segnala'])) {
            $actions[] = ['label' => 'Apri ticket', 'url' => url('/backend/ticket')];
        }

        if (Str::contains($lower, ['cliente', 'codice fiscale', 'telefono'])) {
            $actions[] = ['label' => 'Cerca cliente', 'url' => url('/backend/cliente')];
        }

        if (Str::contains($lower, ['spid', 'assistenza', 'recupero'])) {
            $actions[] = ['label' => 'Richieste assistenza', 'url' => url('/backend/richiesta-assistenza')];
        }

        return $actions;
    }

    protected function conversationMemory(User $user, ?int $conversationId, array $clientHistory): array
    {
        $session = null;

        if ($conversationId) {
            $session = AiConversation::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use ($conversationId) {
                    $query->where('id', $conversationId)
                        ->orWhere('subject_id', $conversationId);
                })
                ->orderBy('id')
                ->first();
        }

        $serverHistory = collect();
        if ($session) {
            $serverHistory = AiConversation::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use ($session) {
                    $query->where('id', $session->id)
                        ->orWhere(function ($query) use ($session) {
                            $query->where('subject_type', AiConversation::class)
                                ->where('subject_id', $session->id);
                        });
                })
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->reverse()
                ->values();
        }

        $last = $serverHistory->last();
        $lastMetadata = (array) ($last?->metadata ?? []);
        $safeClientHistory = collect($clientHistory)
            ->filter(fn ($item) => is_array($item) && isset($item['role'], $item['text']))
            ->map(fn ($item) => [
                'role' => Str::limit((string) $item['role'], 12, ''),
                'text' => Str::limit((string) $item['text'], 280),
            ])
            ->take(-8)
            ->values()
            ->all();

        return [
            'session_id' => $session?->id,
            'last_prompt' => $last?->prompt,
            'last_answer' => $last?->answer,
            'last_intent' => $lastMetadata['intent'] ?? null,
            'workflow' => $lastMetadata['workflow'] ?? null,
            'history' => $safeClientHistory,
        ];
    }

    protected function chatIntent(string $prompt, array $memory = []): string
    {
        $lower = Str::lower(trim($prompt));

        if (in_array($lower, ['si', 'sì', 'ok', 'va bene', 'procedi', 'confermo'], true)) {
            return 'confirm';
        }

        if (in_array($lower, ['no', 'annulla', 'stop', 'lascia stare', 'cancella'], true)) {
            return 'cancel';
        }

        if ($this->isRechargeRequest($prompt)) {
            return 'recharge_plafond';
        }

        if (Str::contains($lower, ['messaggio', 'scrivi', 'risposta cliente'])) {
            return 'draft_message';
        }

        if (Str::contains($lower, ['priorità', 'priorita', 'prossima', 'controllare'])) {
            return 'next_action';
        }

        if (Str::contains($lower, ['crea', 'apri', 'nuovo', 'nuova'])) {
            return 'open_or_create';
        }

        return $memory['last_intent'] ?? 'general';
    }

    protected function continuePreviousAnswer(array $area, array $memory): string
    {
        return match ($memory['last_intent']) {
            'recharge_plafond' => 'Procedo con la ricarica: se il modulo è già aperto, scegli importo e portafoglio. Se non lo vedi, scrivi "ricarica plafond" e lo riapro.',
            'draft_message' => 'Va bene. Mandami nome cliente e cosa vuoi comunicare, poi ti preparo un testo pronto da usare.',
            'next_action' => 'Ok. Restiamo su '.$area['label'].': parti dagli elementi fermi o con dati mancanti, poi chiudiamo una voce alla volta.',
            'open_or_create' => 'Perfetto. Dimmi quali dati hai già e ti indico il punto corretto da aprire.',
            default => 'Ok, continuo da qui. Dimmi il prossimo dettaglio e tengo il contesto della richiesta.',
        };
    }

    protected function isRechargeRequest(string $prompt): bool
    {
        $lower = Str::lower($prompt);

        return Str::contains($lower, ['ricarica', 'ricaricare', 'plafond', 'portafoglio'])
            && Str::contains($lower, ['stripe', 'carta', 'pagamento', 'ricarica', 'plafond']);
    }
}

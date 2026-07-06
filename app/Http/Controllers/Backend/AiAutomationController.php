<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\AiConversation;
use App\Models\AiEvent;
use App\Models\AiSuggestion;
use App\Models\TipoCafPatronato;
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
        $answer = $this->buildChatAnswer($data['prompt'], $area, $audience, $memory, $context);
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

    protected function buildChatAnswer(string $prompt, array $area, string $audience, array $memory = [], array $context = []): string
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

        if ($contextualAnswer = $this->buildContextualPageAnswer($prompt, $area, $context)) {
            return $contextualAnswer;
        }

        if ($intent === 'search') {
            return 'Ok. Dimmi nome, codice fiscale, partita IVA, email o numero pratica. Ti porto nella sezione giusta e mantengo il contesto della ricerca.';
        }

        if ($intent === 'blocked_case') {
            return 'Controllo prima dati mancanti, allegati, esito e ultimo aggiornamento. Se mi dai ID pratica o cliente, ti dico cosa blocca la lavorazione e da dove ripartire.';
        }

        if ($intent === 'massive_action') {
            if ($audience !== 'admin') {
                return 'Le azioni massive sono riservate all’admin. Posso però aiutarti a filtrare gli elementi e aprire quelli da lavorare.';
            }

            return 'Posso preparare una lista controllata, ma prima serve scegliere filtro, elementi e azione. Nessuna modifica massiva parte senza una conferma esplicita.';
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

    protected function buildContextualPageAnswer(string $prompt, array $area, array $context): ?string
    {
        $lower = Str::lower($prompt.' '.($context['title'] ?? '').' '.($context['heading'] ?? '').' '.($context['visible_text'] ?? ''));

        $isExplainRequest = Str::contains($lower, [
            'spiegami', 'spiega', 'cosa è', "cos'è", 'che cos', 'a cosa serve', 'documenti',
            'allegare', 'serve', 'servono', 'come compilo', 'come si compila', 'cosa devo chiedere',
            'checklist', 'questa pagina', 'questa pratica',
        ]);

        if (! $isExplainRequest) {
            return null;
        }

        if ($area['key'] === 'caf-patronato') {
            return $this->cafPatronatoContextAnswer($prompt, $context);
        }

        return match ($area['key']) {
            'visura' => "Sei nella sezione Visure. Qui si richiedono documenti ufficiali come visure camerali, catastali, protesti o CRIF. Prima di creare la pratica verifica tipo visura, dati corretti del soggetto, codice fiscale o partita IVA, eventuali deleghe/documenti e portafoglio visure sufficiente. Se mi dici quale visura stai aprendo, ti preparo la checklist precisa.",
            'spedizione-inpost' => "Sei nelle spedizioni InPost. Qui prepari o controlli spedizioni verso locker/punti InPost. Controlla sempre mittente, destinatario, telefono/email, dimensioni collo, locker o punto scelto, etichetta e tracking. Se una spedizione si blocca, guarda prima stato API, UUID spedizione, tracking e deposito.",
            'spedizione-brt' => "Sei nelle spedizioni BRT. Qui gestisci spedizione, etichetta e tracking. Prima di confermare servono mittente, destinatario, indirizzo completo, CAP, telefono, peso/colli e servizio scelto. Se qualcosa non va, il primo controllo è su indirizzo, CAP e risposta del corriere.",
            'richiesta-assistenza' => "Sei nelle Richieste assistenza. Qui gestisci assistenze SPID o recuperi. Chiedi sempre codice fiscale, documento, contatto, tipo richiesta e se è prima attivazione o recupero di uno SPID già attivo: questo cambia anche il conteggio economico.",
            'cliente-assistenza' => "Sei nei Clienti assistenza. Qui trovi o crei l’anagrafica delle persone seguite per SPID e assistenze. Verifica codice fiscale, email, telefono, credenziali disponibili e storico richieste prima di aprire una nuova assistenza.",
            'ticket' => "Sei nei Ticket. Qui si raccolgono problemi o richieste interne. Per lavorarlo bene servono oggetto chiaro, cliente/pratica collegata, priorità, stato e ultimo messaggio. La prossima azione è assegnare, rispondere o chiudere con esito chiaro.",
            'contratto' => "Sei nei Contratti telefonia. Qui controlli offerte, dati cliente, documenti, stato gestore ed esito finale. Prima di inviare una pratica verifica anagrafica, codice fiscale/partita IVA, recapiti, prodotto scelto, allegati e consenso.",
            'contratto-energia' => "Sei nei Contratti energia. Qui servono dati intestatario, POD/PDR, indirizzo fornitura, documento, codice fiscale, bolletta o dati tecnici e stato pratica. Se è voltura/subentro, controlla anche documenti firmati e link cliente.",
            'documenti' => "Sei in Documenti. Qui si archiviano e consultano file sensibili. Apri solo ciò che serve, evita duplicati, controlla cartella corretta, nome file leggibile e presenza di documenti mancanti. Le operazioni vengono tracciate dall’audit.",
            'agente' => "Sei negli Agenti. Qui controlli profilo, produzione, plafond, permessi e storico movimenti. Per capire un problema guarda prima ruolo, portafogli, pratiche recenti e movimenti collegati.",
            'carica-plafond' => "Sei in Ricarica plafond agenti. Qui l’admin accredita portafoglio agli agenti. Controlla agente, importo, portafoglio corretto e descrizione: ogni movimento modifica subito il saldo.",
            default => 'Sei su '.$area['label'].'. Posso spiegarti cosa fa questa pagina, quali dati controllare, quali documenti servono e qual è il prossimo passo operativo. Dimmi cosa vuoi capire e resto sul contesto corrente.',
        };
    }

    protected function cafPatronatoContextAnswer(string $prompt, array $context): string
    {
        $service = $this->resolveCafServiceFromContext($context);
        $knowledge = $this->cafKnowledge($service);
        $lower = Str::lower($prompt);

        if (Str::contains($lower, ['documenti', 'allegare', 'serve', 'servono', 'checklist', 'cosa devo chiedere'])) {
            return $knowledge['documents'];
        }

        if (Str::contains($lower, ['come compilo', 'come si compila', 'prossimo passo', 'cosa faccio'])) {
            return $knowledge['workflow'];
        }

        return $knowledge['explanation']."\n\n".$knowledge['documents']."\n\n".$knowledge['workflow'];
    }

    protected function resolveCafServiceFromContext(array $context): string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $context['url'] ?? '',
            $context['full_url'] ?? '',
            $context['title'] ?? '',
            $context['heading'] ?? '',
            $context['visible_text'] ?? '',
        ])));

        if (preg_match('#caf-patronato/create/([0-9]+)#', (string) ($context['url'] ?? ''), $matches)) {
            $tipo = TipoCafPatronato::query()->find((int) $matches[1]);
            if ($tipo) {
                return Str::lower((string) $tipo->nome);
            }
        }

        foreach (array_keys($this->cafKnowledgeMap()) as $key) {
            if (Str::contains($haystack, $key)) {
                return $key;
            }
        }

        return 'caf patronato';
    }

    protected function cafKnowledge(string $service): array
    {
        $service = Str::lower($service);
        foreach ($this->cafKnowledgeMap() as $key => $knowledge) {
            if (Str::contains($service, $key)) {
                return $knowledge;
            }
        }

        return [
            'explanation' => 'CAF/Patronato raccoglie e lavora pratiche fiscali, previdenziali o assistenziali per il cliente. L’obiettivo è creare una pratica completa, con dati anagrafici corretti, allegati leggibili e stato aggiornato.',
            'documents' => 'Documenti base da chiedere: documento identità, codice fiscale, recapiti, eventuale delega, documenti specifici della pratica e ogni allegato indicato nella pagina.',
            'workflow' => 'Passi consigliati: identifica il servizio, compila anagrafica e contatti, allega documenti, salva la pratica, controlla il portafoglio scalato e aggiorna l’esito quando la lavorazione avanza.',
        ];
    }

    protected function cafKnowledgeMap(): array
    {
        return [
            '730' => [
                'explanation' => 'Il 730 è la dichiarazione dei redditi usata soprattutto da lavoratori dipendenti e pensionati. Serve a dichiarare redditi, detrazioni e deduzioni, ottenendo eventuale rimborso o addebito direttamente in busta paga o pensione.',
                'documents' => 'Per il 730 chiedi: documento e codice fiscale del dichiarante e familiari a carico, CU, dichiarazione anno precedente, F24, dati datore di lavoro/pensione, redditi esteri o assegni, visure/atti immobili, contratti affitto, spese mediche, mutuo, ristrutturazioni, scuola, assicurazioni, previdenza, erogazioni liberali e ogni ricevuta detraibile o deducibile.',
                'workflow' => 'Per lavorarlo bene: verifica anno fiscale, datore che effettua il conguaglio, familiari a carico, redditi e immobili. Poi allega documenti ordinati per categoria e segnala nelle note ciò che manca.',
            ],
            'isee' => [
                'explanation' => 'L’ISEE misura la situazione economica del nucleo familiare. Serve per bonus, agevolazioni, prestazioni sociali, università e molte pratiche assistenziali.',
                'documents' => 'Per ISEE chiedi: documento e codice fiscale del dichiarante, codici fiscali del nucleo familiare, contratto affitto registrato se presente, certificazioni disabilità, saldo e giacenza media conti, libretti, carte, titoli, patrimonio mobiliare, immobili, mutuo residuo, veicoli, redditi e dati patrimoniali al periodo richiesto.',
                'workflow' => 'Controlla prima composizione nucleo, residenza, presenza affitto/disabilità e patrimonio. Senza giacenze medie e saldi la pratica rischia di fermarsi.',
            ],
            'naspi' => [
                'explanation' => 'La NASpI è l’indennità di disoccupazione per chi ha perso involontariamente il lavoro e possiede i requisiti contributivi.',
                'documents' => 'Per NASpI chiedi: documento, codice fiscale, ultima busta paga, lettera licenziamento o fine contratto, IBAN intestato, recapiti, eventuali contratti recenti e dati del datore di lavoro.',
                'workflow' => 'Verifica data cessazione lavoro, tipo cessazione, IBAN corretto e documenti completi. La tempestività conta: meglio aprirla appena il cliente ha i documenti.',
            ],
            'assegno unico' => [
                'explanation' => 'L’Assegno Unico è il sostegno economico per figli a carico. L’importo dipende da nucleo, figli e ISEE.',
                'documents' => 'Per Assegno Unico chiedi: documento e codice fiscale richiedente, codici fiscali dei figli, IBAN, ISEE se disponibile, eventuali certificazioni disabilità, dati dell’altro genitore se necessari.',
                'workflow' => 'Controlla figli a carico, IBAN intestato o cointestato, ISEE aggiornato e presenza di disabilità. Senza ISEE l’importo può essere minimo.',
            ],
            'invalidita' => [
                'explanation' => 'La pratica di invalidità civile/Legge 104/accompagnamento serve a richiedere riconoscimenti sanitari e benefici collegati alla condizione del cittadino.',
                'documents' => 'Per invalidità chiedi: documento, codice fiscale, certificato medico introduttivo, documentazione sanitaria aggiornata, verbali precedenti, recapiti, eventuale delega e IBAN se richiesto dalla prestazione.',
                'workflow' => 'Prima verifica certificato medico introduttivo e documenti sanitari. Poi allega tutto in modo leggibile e annota la richiesta precisa: invalidità, accompagnamento, 104 o collocamento mirato.',
            ],
            'assegno sociale' => [
                'explanation' => 'L’Assegno Sociale è una prestazione economica per persone con requisiti di età, residenza e reddito basso.',
                'documents' => 'Chiedi documento, codice fiscale, residenza, redditi, stato civile, permesso soggiorno se necessario, IBAN e documentazione reddituale/patrimoniale richiesta.',
                'workflow' => 'Controlla requisiti anagrafici, residenza effettiva e redditi. Se mancano dati reddituali la pratica va sospesa in attesa integrazione.',
            ],
            'bonus asilo' => [
                'explanation' => 'Il Bonus Asilo Nido è il contributo per rette di asili nido o supporto domiciliare in presenza di gravi patologie.',
                'documents' => 'Chiedi documento, codice fiscale genitore e minore, ricevute rette, iscrizione/asilo, IBAN e ISEE minorenni se disponibile.',
                'workflow' => 'Verifica intestazione ricevute, dati del minore e IBAN. Allegare ricevute chiare evita richieste di integrazione.',
            ],
            'contratto locazione' => [
                'explanation' => 'La pratica di contratto di locazione riguarda registrazione, adempimenti successivi o gestione fiscale del contratto di affitto.',
                'documents' => 'Chiedi documento e codice fiscale locatore/conduttore, contratto, dati immobile, rendita catastale, eventuali allegati, ricevute, opzione cedolare secca e adempimento richiesto.',
                'workflow' => 'Controlla soggetti, immobile, date, canone, regime fiscale e tipo adempimento. Un errore su date o dati catastali blocca la lavorazione.',
            ],
        ];
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

        if (Str::contains($lower, ['cliente', 'codice fiscale', 'telefono', 'partita iva', 'email', 'cerca', 'trova'])) {
            $actions[] = ['label' => 'Cerca cliente', 'url' => url('/backend/cliente')];
            $actions[] = ['label' => 'Clienti assistenza', 'url' => url('/backend/cliente-assistenza')];
        }

        if (Str::contains($lower, ['spid', 'assistenza', 'recupero'])) {
            $actions[] = ['label' => 'Richieste assistenza', 'url' => url('/backend/richiesta-assistenza')];
        }

        if (Str::contains($lower, ['bloccata', 'bloccato', 'ferma', 'fermo', 'documenti mancanti'])) {
            $actions[] = ['label' => 'CAF / Patronato', 'url' => url('/backend/caf-patronato')];
            $actions[] = ['label' => 'Visure', 'url' => url('/backend/visura')];
        }

        if (Str::contains($lower, ['massiva', 'massivo', 'assegna tutto', 'chiudi tutti', 'sollecita'])) {
            $actions[] = ['label' => 'Dashboard admin', 'url' => url('/backend/lavoro')];
            $actions[] = ['label' => 'Agenti', 'url' => url('/backend/agente')];
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

        if (Str::contains($lower, ['cerca', 'trova', 'cliente', 'codice fiscale', 'partita iva', 'email', 'telefono'])) {
            return 'search';
        }

        if (Str::contains($lower, ['bloccata', 'bloccato', 'ferma', 'fermo', 'perché non va', 'perche non va', 'documenti mancanti'])) {
            return 'blocked_case';
        }

        if (Str::contains($lower, ['massiva', 'massivo', 'assegna tutto', 'chiudi tutti', 'azioni massive', 'sollecita agenti'])) {
            return 'massive_action';
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
            'search' => 'Perfetto. Mandami il dato da cercare e ti indico la sezione più adatta.',
            'blocked_case' => 'Va bene. Mandami ID pratica o cliente e controllo dati mancanti, allegati, stato e prossima azione.',
            'massive_action' => 'Ok. Prima preparo il filtro e il riepilogo. Applico modifiche massive solo dopo conferma admin.',
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

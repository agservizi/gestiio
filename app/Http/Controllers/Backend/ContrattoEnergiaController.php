<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\SottoClassiEnergia\ProdottoEnergiaAbstract;
use App\Http\Controllers\Controller;

use App\Models\AllegatoContrattoEnergia;
use App\Models\Cliente;
use App\Models\EsitoContrattoEnergia;
use App\Models\GestoreContrattoEnergia;
use App\Models\Notifica;
use App\Models\TabMotivoKo;
use App\Models\User;
use App\Notifications\NotificaAdminContrattoEnergia;
use App\Notifications\NotificaAgenteCambioEsitoContrattoEnergia;
use App\Notifications\NotificaAgenteNuovoContrattoEnergia;
use App\Notifications\NotificaClienteContrattoEnergia;
use App\Notifications\NotificaDatiAccessoClienteContrattoEnergia;
use App\Notifications\NotificaGenericaGestoreContrattoEnergia;
use App\Rules\CodiceFiscaleRule;
use App\Rules\DataItalianaRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContrattoEnergia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputToUpper;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\ChatThreadUser;
use App\Events\ChatMessageSent;
use App\Jobs\SendChatWebPushNotification;

class ContrattoEnergiaController extends Controller
{
    protected $conFiltro = false;

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function segnalaChat(Request $request, $id)
    {
        $request->validate([
            'messaggio' => ['required', 'string', 'max:3000'],
        ]);

        $contratto = ContrattoEnergia::find($id);
        abort_if(!$contratto, 404, 'Questo contratto non esiste');

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Determine the recipient based on who is sending:
        // - If sender is admin: deliver to the contract's agent (uploader) if available, otherwise to an available agent/supervisor.
        // - If sender is not admin: deliver to an admin (prefer id 2 if different), otherwise fallback to an available operator.
        $recipient = null;
        if ($authUser->hasPermissionTo('admin')) {
            if (!empty($contratto->agente_id) && $contratto->agente_id !== $authUser->id) {
                $recipient = User::find($contratto->agente_id);
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->whereIn('name', ['agente', 'supervisore']);
                })->where('id', '<>', $authUser->id)->first();
            }
        } else {
            // non-admin sender -> send to admin
            $recipient = User::find(2);
            if ($recipient && $recipient->id === $authUser->id) {
                $recipient = null;
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->where('name', 'admin');
                })->where('id', '<>', $authUser->id)->first();
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->whereIn('name', ['agente', 'supervisore']);
                })->where('id', '<>', $authUser->id)->first();
            }
        }
        abort_if(!$recipient, 404, 'Destinatario non trovato');

        // Cerca thread esistente tra i due utenti
        $thread = ChatThread::query()
            ->whereHas('partecipanti', function ($q) use ($authUser) {
                $q->where('users.id', $authUser->id);
            })
            ->whereHas('partecipanti', function ($q) use ($recipient) {
                $q->where('users.id', $recipient->id);
            })
            ->whereDoesntHave('partecipanti', function ($q) use ($authUser, $recipient) {
                $q->whereNotIn('users.id', [$authUser->id, $recipient->id]);
            })
            ->latest('id')
            ->first();

        if (!$thread) {
            $thread = new ChatThread();
            $thread->created_by = $authUser->id;
            $thread->save();
            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $recipient->id => ['last_read_at' => null],
            ]);
        }

        $testo = trim((string)$request->input('messaggio'));
        $messaggio = new ChatMessage();
        $messaggio->thread_id = $thread->id;
        $messaggio->user_id = $authUser->id;
        $messaggio->messaggio = "Segnalazione contratto #{$contratto->id} ({$contratto->codice_contratto}) - " . $authUser->nominativo() . "\n\n" . $testo;
        $messaggio->save();
        $messaggio->load('mittente');

        $thread->touch();

        broadcast(new ChatMessageSent($messaggio))->toOthers();

        // Notifica via email se primo messaggio non letto e push
        $recipient->notify(new \App\Notifications\NotificaPrimoMessaggioChatInterna($messaggio));

        SendChatWebPushNotification::dispatch($recipient->id, [
            'title' => $authUser->nominativo() . ' · Chat interna',
            'body' => Str::limit(strip_tags((string) ($messaggio->messaggio ?: 'Segnalazione in chat')), 120),
            'url' => url('/backend/chat-interna?thread=' . $thread->id),
            'thread_id' => (int) $thread->id,
            'message_id' => (int) $messaggio->id,
            'tag' => 'chat-thread-' . $thread->id,
            'icon' => url('/images/logo_small_icon_only.png'),
            'badge' => url('/images/logo_small_icon_only.png'),
        ]);

        return response()->json(['ok' => true, 'message' => 'Segnalazione inviata in chat', 'thread_id' => $thread->id]);
    }

    public function apriChat(Request $request, $id)
    {
        $contratto = ContrattoEnergia::find($id);
        abort_if(!$contratto, 404, 'Questo contratto non esiste');

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Determine recipient: symmetric to segnalaChat logic
        $recipient = null;
        if ($authUser->hasPermissionTo('admin')) {
            if (!empty($contratto->agente_id) && $contratto->agente_id !== $authUser->id) {
                $recipient = User::find($contratto->agente_id);
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->whereIn('name', ['agente', 'supervisore']);
                })->where('id', '<>', $authUser->id)->first();
            }
        } else {
            $recipient = User::find(2);
            if ($recipient && $recipient->id === $authUser->id) {
                $recipient = null;
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->where('name', 'admin');
                })->where('id', '<>', $authUser->id)->first();
            }
            if (!$recipient) {
                $recipient = User::whereHas('permissions', function ($q) {
                    $q->whereIn('name', ['agente', 'supervisore']);
                })->where('id', '<>', $authUser->id)->first();
            }
        }
        abort_if(!$recipient, 404, 'Destinatario non trovato');

        // trova o crea thread tra authUser e recipient
        $thread = ChatThread::query()
            ->whereHas('partecipanti', function ($q) use ($authUser) {
                $q->where('users.id', $authUser->id);
            })
            ->whereHas('partecipanti', function ($q) use ($recipient) {
                $q->where('users.id', $recipient->id);
            })
            ->whereDoesntHave('partecipanti', function ($q) use ($authUser, $recipient) {
                $q->whereNotIn('users.id', [$authUser->id, $recipient->id]);
            })
            ->latest('id')
            ->first();

        if (!$thread) {
            $thread = new ChatThread();
            $thread->created_by = $authUser->id;
            $thread->save();
            $thread->partecipanti()->attach([
                $authUser->id => ['last_read_at' => now()],
                $recipient->id => ['last_read_at' => null],
            ]);
        }

        // crea messaggio con riferimento al contratto
        $messaggio = new ChatMessage();
        $messaggio->thread_id = $thread->id;
        $messaggio->user_id = $authUser->id;
        $messaggio->messaggio = "Segnalazione contratto #{$contratto->id} ({$contratto->codice_contratto}) da " . $authUser->nominativo();
        $messaggio->save();
        $messaggio->load('mittente');

        // allega primo file del contratto, se presente
        $allegato = AllegatoContrattoEnergia::where('contratto_energia_id', $contratto->id)->first();
        if ($allegato) {
            $attachment = new \App\Models\ChatMessageAttachment();
            $attachment->message_id = $messaggio->id;
            $attachment->filename_originale = $allegato->filename_originale;
            $attachment->path_filename = ltrim($allegato->path_filename, '/');
            $attachment->mime_type = $allegato->mime_type ?? 'application/octet-stream';
            $attachment->dimensione_file = $allegato->dimensione_file;
            $attachment->scan_status = 'clean';
            $attachment->scan_note = 'Allegato contratto inviato in segnalazione';
            $attachment->is_blocked = false;
            $attachment->save();
        }

        $thread->touch();

        broadcast(new ChatMessageSent($messaggio))->toOthers();

        // notify recipient
        $recipient->notify(new \App\Notifications\NotificaPrimoMessaggioChatInterna($messaggio));

        SendChatWebPushNotification::dispatch($recipient->id, [
            'title' => $authUser->nominativo() . ' · Chat interna',
            'body' => Str::limit(strip_tags((string) ($messaggio->messaggio ?: 'Segnalazione in chat')), 120),
            'url' => url('/backend/chat-interna?thread=' . $thread->id),
            'thread_id' => (int) $thread->id,
            'message_id' => (int) $messaggio->id,
            'tag' => 'chat-thread-' . $thread->id,
        ]);

        return response()->json(['ok' => true, 'thread_id' => $thread->id]);
    }


    /**
     * Display a listing of the resource.
     *
    * @return mixed
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);
        $giorniFermo = max(1, (int)$request->input('giorni_fermo', 7));

        $ordinamenti = [
            'data' => ['testo' => 'Data', 'filtro' => function ($q) {
                return $q->orderByDesc('data')->orderByDesc('id');
            }],
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],
            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('nome');
            }]
        ];

        $orderByUser = $this->currentUser()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } else if ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            $this->currentUser()->setExtra([$nomeClasse => $orderBy]);
        }

        //Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $contrattiFermiCount = (clone $recordsQB)
            ->whereIn('esito_id', ['bozza', 'da-gestire'])
            ->whereDate('created_at', '<=', now()->subDays($giorniFermo))
            ->count();

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        $puoModificare = $this->determinaPuoModificare();
        $puoCambiareStato = $this->determinaPuoCambiareStato();
        $puoCreare = $this->determinaPuoCreare();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.ContrattoEnergia.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare,
                    'puoCambiareStato' => $puoCambiareStato,
                    'puoCreare' => $puoCreare,

                ])->render())
            ];

        }


        return view('Backend.ContrattoEnergia.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ContrattoEnergia::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\ContrattoEnergia::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nominativo, email, telefono',
            'puoModificare' => $puoModificare,
            'puoCambiareStato' => $puoCambiareStato,
            'puoCreare' => $puoCreare,
            'giorniFermo' => $giorniFermo,
            'contrattiFermiCount' => $contrattiFermiCount,


        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ContrattoEnergia::query()
            ->with(['esito' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['gestore' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['agente' => function ($q) {
                $q->select('id', 'alias');
            }])
            ->withCount('allegati');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',testo_ricerca,email,telefono,codice_fiscale,codice_contratto)'), 'like', "%$t%");
            }
        }

        if ($request->input('esiti')) {
            $stati = $request->input('esiti');
            $queryBuilder->where(function ($q) use ($stati) {
                foreach ($stati as $stato) {
                    $q->orWhere('esito_id', '=', $stato);
                }
            });
            $this->conFiltro = true;
        }

        if ($request->input('mese') && $request->input('anno')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate($request->input('anno'), $request->input('mese'), 1);
            $dataA = $dataDa->copy()->endOfMonth();
            $queryBuilder->whereDate('created_at', '>=', $dataDa)->whereDate('created_at', '<=', $dataA);
        } elseif ($request->input('mese')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate(Carbon::today()->year, $request->input('mese'), 1);
            $dataA = $dataDa->copy()->endOfMonth();
            $queryBuilder->whereDate('created_at', '>=', $dataDa)->whereDate('created_at', '<=', $dataA);
        } elseif ($request->input('anno')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate($request->input('anno'), 1, 1);
            $dataA = $dataDa->copy()->endOfYear();
            $queryBuilder->whereDate('created_at', '>=', $dataDa)->whereDate('created_at', '<=', $dataA);
        }


        if ($request->has('agente_id')) {
            $queryBuilder->where('agente_id', $request->input('agente_id'));
            $this->conFiltro = true;

        }

        if ($request->boolean('solo_fermi')) {
            $giorniFermo = max(1, (int)$request->input('giorni_fermo', 7));
            $queryBuilder
                ->whereIn('esito_id', ['bozza', 'da-gestire'])
                ->whereDate('created_at', '<=', now()->subDays($giorniFermo));
            $this->conFiltro = true;
        }

        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
    * @return mixed
     */
    public function create(Request $request, $gestoreId = null)
    {


        if (!$gestoreId) {
            $record = new ContrattoEnergia();
            if ($this->currentUser()->hasPermissionTo('agente')) {
                $record->agente_id = Auth::id();
            }
            return view('Backend.ContrattoEnergia.modalNuovo', [
                'servizi' => \App\Models\GestoreContrattoEnergia::orderBy('nome')->where('attivo', 1)->get(),
                'record' => $record,
                'controller' => get_class($this),
                'titoloPagina' => 'Nuovo ContrattoEnergia'
            ]);
        }

        $prodotto = null;


        $record = new ContrattoEnergia();
        if ($this->currentUser()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        } else {
            $record->agente_id = $request->input('agente_id');
        }
        $record->gestore_id = $gestoreId;

        $gestore = GestoreContrattoEnergia::find($record->gestore_id);
        abort_if(!$gestore, 404, 'Questo gestore non esiste');

        $categoriaRichiesta = $request->input('categoria_pratica');
        if (!$categoriaRichiesta) {
            $categoriaRichiesta = 'business';
        }

        if (in_array($categoriaRichiesta, ['consumer', 'business'], true)) {
            $gestoreTarget = $this->resolveGestoreByCategoria($gestore, $categoriaRichiesta);
            if ($gestoreTarget && $gestoreTarget->id !== $gestore->id) {
                $query = array_merge(
                    ['categoria_pratica' => $categoriaRichiesta],
                    $this->extractCreatePrefillData($request)
                );

                $url = action([self::class, 'create'], [$gestoreTarget->id]);
                $url .= '?' . http_build_query($query);

                return redirect()->to($url);
            }
        }

        if ($gestore->model_prodotto) {
            $classe = 'App\Models\\' . $gestore->model_prodotto;
            $prodotto = $this->presetCampi(new $classe(), $gestore->model_prodotto);
        }

        $record->uid = Str::ulid();
        $record->data = today();
        $this->applyCreatePrefillToContratto($record, $request);
        $this->applyCreatePrefillToProdotto($prodotto, $request);


        $categoriaPratica = $this->determinaCategoriaPratica($record, $request, $gestore->model_prodotto);
        $categoriaSwitchUrls = $this->buildCategoriaSwitchUrls($gestore, $record->agente_id);

        return view('Backend.ContrattoEnergia.edit', [
            'record' => $record,
            'contratto' => $record,
            'titoloPagina' => 'Nuovo ' . ContrattoEnergia::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ContrattoEnergiaController::class, 'index']) => 'Torna a elenco ' . ContrattoEnergia::NOME_PLURALE],
            'recordProdotto' => $prodotto,
            'tipoProdotto' => $gestore->model_prodotto,
            'creaContratto' => false,
            'categoriaPratica' => $categoriaPratica,
            'categoriaSwitchUrls' => $categoriaSwitchUrls,
        ]);
    }


    protected function presetCampi($record, $prodotto)
    {
        switch ($prodotto) {
            case 'ProdottoEnergiaEgea':
                $record->spedizione_fattura = 'posta_ordinaria';
                break;

            case 'ProdottoEnelConsumer':
                $record->modalita_pagamento = 'bollettino';
                break;

        }

        return $record;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
    * @return mixed
     */
    public function store(Request $request)
    {

        $tipoProdotto = $request->input('tipo_prodotto');
        $rules = $this->rules($request, null);
        $classeProdotto = null;
        if ($tipoProdotto) {
            $classeProdotto = ProdottoEnergiaAbstract::constructor($tipoProdotto);
            $rules = array_merge($rules, $classeProdotto->rulesProdotto(null));
        }

        $request->validate($rules);
        $record = new ContrattoEnergia();
        $this->salvaDati($record, $request, $classeProdotto);

        if ($record->esito_id !== 'bozza') {
            $this->inviaNotifiche($record);
        }

        if ($this->currentUser()->hasPermissionTo('agente')) {
            Notifica::notificaAdAdmin('Nuovo Contratto Energia', '<span class="fw-bold">' . $record->gestore->nome . '</span> caricato da <span class="fw-bold">' . $record->agente->nominativo() . '</span> per il cliente <span class="fw-bold">' . $record->nominativo() . '</span>');
        }

        return redirect()->action([ContrattoEnergiaController::class, 'show'], $record->id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
    * @return mixed
     */
    public function show($id)
    {
        $record = ContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo ContrattoEnergia non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        $puoCreare = $this->currentUser()->hasAnyPermission(['admin', 'agente']);


        return view('Backend.ContrattoEnergia.show', [
            'record' => $record,
            'controller' => ContrattoEnergiaController::class,
            'titoloPagina' => ucfirst(ContrattoEnergia::NOME_SINGOLARE) . ' ' . $record->nominativo(),
            'breadcrumbs' => [action([ContrattoEnergiaController::class, 'index']) => 'Torna a elenco ' . ContrattoEnergia::NOME_PLURALE],
            'puoCreare' => $puoCreare

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
    * @return mixed
     */
    public function edit($id)
    {
        $record = ContrattoEnergia::with('gestore')->find($id);
        abort_if(!$record, 404, 'Questo ContrattoEnergia non esiste');

        abort_if(!$record->puoModificare($this->currentUser()->hasPermissionTo('admin')), 404, 'Non puo modificare questo ContrattoEnergia');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        $recordProdotto = null;
        $tipoProdotto = $record->gestore->model_prodotto;
        if ($tipoProdotto) {
            $recordProdotto = $record->prodotto;
            if (!$recordProdotto) {
                $classe = 'App\Models\\' . $tipoProdotto;
                $recordProdotto = new $classe();

            }

        }


        $categoriaPratica = $this->determinaCategoriaPratica($record, request(), $tipoProdotto);

        return view('Backend.ContrattoEnergia.edit', [
            'record' => $record,
            'contratto' => $record,

            'controller' => ContrattoEnergiaController::class,
            'titoloPagina' => 'Modifica ' . ContrattoEnergia::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ContrattoEnergiaController::class, 'index']) => 'Torna a elenco ' . ContrattoEnergia::NOME_PLURALE],
            'recordProdotto' => $recordProdotto,
            'tipoProdotto' => $tipoProdotto,
            'creaContratto' => true,
            'categoriaPratica' => $categoriaPratica,
            'categoriaSwitchUrls' => [],

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
    * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = ContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo ' . ContrattoEnergia::NOME_SINGOLARE . ' non esiste');

        $tipoProdotto = $request->input('tipo_prodotto');
        $rules = $this->rules($request, $id);
        $classeProdotto = null;
        if ($tipoProdotto) {
            $classeProdotto = ProdottoEnergiaAbstract::constructor($tipoProdotto);
            $rules = array_merge($rules, $classeProdotto->rulesProdotto(null));
        }

        $request->validate($rules);
        $esitoPrima = $record->esito_id;
        $this->salvaDati($record, $request, $classeProdotto);

        if ($esitoPrima == 'bozza' && $record->esito_id == 'da-gestire') {
            $this->inviaNotifiche($record);
        }


        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
    * @return mixed
     */
    public function destroy($id)
    {
        $record = ContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo ContrattoEnergia non esiste');

        foreach ($record->allegati as $allegato) {
            $allegato->delete();
        }
        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ContrattoEnergiaController::class, 'index']),
        ];
    }


    public function downloadAllegato($ContrattoEnergiaId, $allegatoId)
    {
        $record = AllegatoContrattoEnergia::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->contratto_energia_id != $ContrattoEnergiaId, 404, 'Questo allegato non esiste');

        if ($record->file_contenuto_base64) {
            $contenuto = base64_decode($record->file_contenuto_base64, true);
            if ($contenuto !== false) {
                return response($contenuto, 200, [
                    'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . addslashes($record->filename_originale) . '"',
                ]);
            }
        }

        return response()->download(Storage::path($record->path_filename), $record->filename_originale);
    }

    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoContrattoEnergia();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_contratti_energia.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->mime_type = $filePath->getMimeType();
            $contenuto = file_get_contents($filePath->getRealPath());
            $file->file_contenuto_base64 = $contenuto !== false ? base64_encode($contenuto) : null;
            $file->uid = $request->input('uid');
            $file->dimensione_file = $filePath->getSize();
            if ($request->input('contratto_energia_id') > 0) {
                $file->contratto_energia_id = $request->input('contratto_energia_id');
            }
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoContrattoEnergia::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }

    public function aggiornaStato(Request $request, $id)
    {
        $ContrattoEnergia = ContrattoEnergia::withCount('allegati')->find($id);
        abort_if(!$ContrattoEnergia, 404, 'Questo ContrattoEnergia non esiste');

        $esitoPrima = $ContrattoEnergia->esito_id;

        $esito = EsitoContrattoEnergia::find($request->input('esito_id'));
        $motivoKoprima = $ContrattoEnergia->motivo_ko;
        if ($this->currentUser()->hasPermissionTo('admin')) {
            $ContrattoEnergia->pagato = getInputCheckbox($request->input('pagato'));
        }
        $ContrattoEnergia->codice_contratto = getInputToUpper($request->input('codice_contratto'));
        $ContrattoEnergia->esito_id = $esito->id;
        $ContrattoEnergia->esito_finale = $esito->esito_finale;
        $ContrattoEnergia->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));
        $ContrattoEnergia->save();

        if ($ContrattoEnergia->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($ContrattoEnergia->motivo_ko && strlen($ContrattoEnergia->motivo_ko) < 70) {
                $tab = TabMotivoKo::firstOrNew(['nome' => $ContrattoEnergia->motivo_ko, 'tipo' => 'contratto-energia']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$ContrattoEnergia]);


        if ($esitoPrima == 'bozza') {
            $this->inviaNotifiche($ContrattoEnergia);
        }


        if ($ContrattoEnergia->wasChanged(['esito_id'])) {
            $esito = EsitoContrattoEnergia::find($ContrattoEnergia->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($ContrattoEnergia) {
                    $agente = $ContrattoEnergia->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaAgenteCambioEsitoContrattoEnergia($ContrattoEnergia));
                    }
                })->afterResponse();

            }

            if ($request->input('ruolo') == 'supervisore') {
                Notifica::notificaAdAdmin('Cambio stato', 'Esito per il ContrattoEnergia di ' . $ContrattoEnergia->nominativo() . ' modificato a ' . $esito->nome);
            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.contratti';
        } else {
            $view = 'Backend.ContrattoEnergia.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => ContrattoEnergiaController::class,
                'puoModificare' => $this->determinaPuoModificare(),
                'puoCreare' => $this->determinaPuoCreare(),
                'puoCambiareStato' => $this->determinaPuoCambiareStato(),
            ])->render())
        ];
    }


    /**
     * @param ContrattoEnergia $ContrattoEnergia
     * @return void
     */
    public function inviaNotifiche($ContrattoEnergia)
    {


        $this->creaUtente($ContrattoEnergia);


        dispatch(function () use ($ContrattoEnergia) {
            //Notifica a agente
            $user = $ContrattoEnergia->agente;
            try {
                $user->notify(new NotificaAgenteNuovoContrattoEnergia($ContrattoEnergia));

            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'ad agente per il ContrattoEnergia di ' . $ContrattoEnergia->nominativo() . ': ' . $exception->getMessage(), 'error');
            }

        })->afterResponse();

        //Notifiche al gestore

        if ($ContrattoEnergia->gestore->email_notifica_a_gestore) {

            dispatch(function () use ($ContrattoEnergia) {
                foreach (explode(';', $ContrattoEnergia->gestore->email_notifica_a_gestore) as $email) {
                    $user = new User();
                    $user->email = trim($email);
                    try {
                        $user->notify(new NotificaGenericaGestoreContrattoEnergia($ContrattoEnergia));
                    } catch (\Exception $exception) {
                        report($exception);
                        Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il ContrattoEnergia di ' . $ContrattoEnergia->nominativo() . ': ' . $exception->getMessage(), 'error');
                    }
                }
            })->afterResponse();

        }

        if ($this->currentUser()->hasPermissionTo('agente')) {
            dispatch(function () use ($ContrattoEnergia) {
                $user = new User();
                $user->email = 'noreply@gestiio.it';
                try {
                    $user->notify(new NotificaAdminContrattoEnergia($ContrattoEnergia));

                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il ContrattoEnergia di ' . $ContrattoEnergia->nominativo() . ': ' . $exception->getMessage(), 'error');

                }
            })->afterResponse();

        }

    }

    /**
     * @param ContrattoEnergia $model
     * @param Request $request
     * @param ProdottoEnergiaAbstract $classeProdotto
     * @return mixed
     */
    protected function salvaDati($model, $request, $classeProdotto)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->esito_id = 'da-gestire';
            $model->testo_ricerca = '|';
            $model->caricato_da_user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'gestore_id' => '',
            'codice_fiscale' => 'strtoupper',
            'email' => 'strtolower',
            'telefono' => 'app\getInputTelefono',
            'note' => '',
            'uid' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if (!$model->cliente_id) {
            $cliente = Cliente::where('codice_fiscale', $model->codice_fiscale)->first();
            if (!$cliente) {
                $cliente = new Cliente();
            }
        } else {
            $cliente = Cliente::find($model->cliente_id);
        }

        $model->cliente_id = $this->salvaDatiCliente($cliente, $request);

        if ($request->input('bozza')) {
            $model->esito_id = 'bozza';
        } elseif ($model->esito_id == 'bozza') {
            $model->esito_id = 'da-gestire';
        }


        $provvigioneAgente = 0;
        if ($classeProdotto) {
            $provvigioneAgente = $classeProdotto->determinaProvvigione($request);
        }
        $model->provvigione_agente = $provvigioneAgente;


        $model->save();


        AllegatoContrattoEnergia::where('uid', $model->uid)->whereNull('contratto_energia_id')->update(['contratto_energia_id' => $model->id, 'uid' => null]);


        if ($classeProdotto) {
            $classeProdotto->salvaDatiProdotto($model, $request);
        }
        return $model;
    }


    /**
     * @param Cliente $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiCliente($model, $request)
    {

        if (!$request->input('cognome')) {
            return null;
        }
        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'codice_fiscale' => 'strtoupper',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();


        return $model->id;
    }

    /**
     * @param ContrattoEnergia $ContrattoEnergia
    * @return void
     */
    protected function creaUtente(ContrattoEnergia $ContrattoEnergia)
    {
        $cliente = Cliente::find($ContrattoEnergia->cliente_id);

        if ($cliente && $cliente->email && $cliente->telefono) {
            $user = User::where('email', $cliente->email)->orWhere('telefono', $cliente->telefono)->first();
            if (!$user) {
                $user = new User();
                $user->nome = $cliente->nome;
                $user->cognome = $cliente->cognome;
                $user->email = $cliente->email;
                $password = rand(11111111, 99999999);
                $user->password = Hash::make($password);
                $user->telefono = $cliente->telefono;
                $user->save();
                $cliente->user_id = $user->id;
                $cliente->save();

                dispatch(function () use ($ContrattoEnergia, $password, $user) {
                    //Notifica a cliente
                    try {
                        $user->notify(new NotificaDatiAccessoClienteContrattoEnergia($ContrattoEnergia, $password));
                        $user->invio_dati_accesso = now();
                        $user->save();
                    } catch (\Exception $exception) {
                        report($exception);
                        Notifica::notificaAdAdmin('Errore nell\'invio dati accesso cliente', 'a ' . $user->nominativo() . ': ' . $exception->getMessage(), 'error');

                    }
                })->afterResponse();

            } else {
                dispatch(function () use ($ContrattoEnergia, $user) {
                    //Notifica a cliente
                    $user->notify(new NotificaClienteContrattoEnergia($ContrattoEnergia));
                })->afterResponse();

            }

        }


    }


    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return \App\Models\ContrattoEnergia::get();
    }


    protected function rules(Request $request, $id = null)
    {
        $categoriaPratica = $this->determinaCategoriaPraticaDaRequest($request);
        $isBusiness = $categoriaPratica === 'business';

        $rules = [
            'data' => ['required', new DataItalianaRule()],
            'agente_id' => ['required'],
            'gestore_id' => ['required'],
            'categoria_pratica' => ['required', 'in:consumer,business'],
            'codice_fiscale' => ['required', new CodiceFiscaleRule()],
            'denominazione' => $isBusiness ? ['nullable', 'max:255'] : ['nullable', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['required', new \App\Rules\TelefonoRule()],
        ];

        return $rules;
    }

    protected function determinaCategoriaPratica(ContrattoEnergia $record, Request $request, ?string $tipoProdotto = null): string
    {
        $categoriaDaProdotto = $this->categoriaDaTipoProdotto($tipoProdotto);
        if ($categoriaDaProdotto !== null) {
            return $categoriaDaProdotto;
        }

        $oldCategoria = old('categoria_pratica');
        if (in_array($oldCategoria, ['consumer', 'business'], true)) {
            return $oldCategoria;
        }

        return $this->determinaCategoriaPraticaDaRequest($request);
    }

    protected function determinaCategoriaPraticaDaRequest(Request $request): string
    {
        $categoriaDaRequest = $request->input('categoria_pratica');
        if (in_array($categoriaDaRequest, ['consumer', 'business'], true)) {
            return $categoriaDaRequest;
        }

        $categoriaDaTipoProdotto = $this->categoriaDaTipoProdotto($request->input('tipo_prodotto'));
        if ($categoriaDaTipoProdotto !== null) {
            return $categoriaDaTipoProdotto;
        }

        return 'consumer';
    }

    protected function categoriaDaTipoProdotto(?string $tipoProdotto): ?string
    {
        if (!$tipoProdotto) {
            return null;
        }

        $nome = strtolower($tipoProdotto);
        if (str_contains($nome, 'business')) {
            return 'business';
        }
        if (str_contains($nome, 'consumer')) {
            return 'consumer';
        }

        return null;
    }

    protected function buildCategoriaSwitchUrls(GestoreContrattoEnergia $gestoreCorrente, ?int $agenteId = null): array
    {
        $targets = [
            'consumer' => $this->resolveGestoreByCategoria($gestoreCorrente, 'consumer'),
            'business' => $this->resolveGestoreByCategoria($gestoreCorrente, 'business'),
        ];

        $urls = [
            'consumer' => null,
            'business' => null,
        ];

        foreach ($targets as $categoria => $gestore) {
            if (!$gestore) {
                continue;
            }

            $query = ['categoria_pratica' => $categoria];
            if ($agenteId) {
                $query['agente_id'] = $agenteId;
            }

            $url = action([self::class, 'create'], [$gestore->id]) . '?' . http_build_query($query);
            $urls[$categoria] = $url;
        }

        return $urls;
    }

    protected function extractCreatePrefillData(Request $request): array
    {
        $allowed = [
            'agente_id',
            'codice_fiscale',
            'email',
            'telefono',
            'cellulare',
            'denominazione',
            'nome',
            'cognome',
            'partita_iva',
            'forma_giuridica',
            'indirizzo',
            'citta',
            'cap',
            'scala',
            'interno',
            'indirizzo_sede',
            'comune_sede',
            'cap_sede',
            'nr_sede',
            'nome_cognome_referente',
            'codice_fiscale_referente',
            'telefono_referente',
            'codice_destinatario',
            'indirizzo_pec',
            'pod',
            'pdr',
            'iban',
        ];

        $data = [];
        foreach ($allowed as $campo) {
            $valore = $request->input($campo);
            if (is_string($valore)) {
                $valore = trim($valore);
            }
            if ($valore === null || $valore === '') {
                continue;
            }
            $data[$campo] = $valore;
        }

        $data = $this->normalizzaPrefillData($data);

        return $data;
    }

    protected function applyCreatePrefillToContratto(ContrattoEnergia $record, Request $request): void
    {
        $prefill = $this->extractCreatePrefillData($request);
        $record->agente_id = $this->prefillValue($prefill, ['agente_id'], $record->agente_id);
        $record->codice_fiscale = $this->prefillValue($prefill, ['codice_fiscale'], $record->codice_fiscale);
        $record->email = $this->prefillValue($prefill, ['email'], $record->email);
        $record->telefono = $this->prefillValue($prefill, ['telefono'], $record->telefono);
        $record->denominazione = $this->prefillValue($prefill, ['denominazione'], $record->denominazione);
    }

    protected function applyCreatePrefillToProdotto($prodotto, Request $request): void
    {
        if (!$prodotto) {
            return;
        }

        $prefill = $this->extractCreatePrefillData($request);
        foreach ([
            'codice_fiscale',
            'email',
            'telefono',
            'cellulare',
            'denominazione',
            'nome',
            'cognome',
            'partita_iva',
            'forma_giuridica',
            'indirizzo',
            'citta',
            'cap',
            'scala',
            'interno',
            'indirizzo_sede',
            'comune_sede',
            'cap_sede',
            'nr_sede',
            'nome_cognome_referente',
            'codice_fiscale_referente',
            'telefono_referente',
            'codice_destinatario',
            'indirizzo_pec',
            'pod',
            'pdr',
            'iban',
        ] as $campo) {
            if (!array_key_exists($campo, $prefill)) {
                continue;
            }
            $this->setModelColumnValue($prodotto, $campo, $prefill[$campo]);
        }

        // Mapping incrociato Consumer <-> Business
        $indirizzo = $this->prefillValue($prefill, ['indirizzo', 'indirizzo_sede']);
        $citta = $this->prefillValue($prefill, ['citta', 'comune_sede']);
        $cap = $this->prefillValue($prefill, ['cap', 'cap_sede']);

        $this->setModelColumnValue($prodotto, 'indirizzo', $indirizzo);
        $this->setModelColumnValue($prodotto, 'citta', $citta);
        $this->setModelColumnValue($prodotto, 'cap', $cap);

        $this->setModelColumnValue($prodotto, 'indirizzo_sede', $indirizzo);
        $this->setModelColumnValue($prodotto, 'comune_sede', $citta);
        $this->setModelColumnValue($prodotto, 'cap_sede', $cap);
    }

    protected function prefillValue(array $prefill, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $prefill)) {
                continue;
            }
            return $prefill[$key];
        }

        return $default;
    }

    protected function normalizzaPrefillData(array $data): array
    {
        if (empty($data['indirizzo']) && !empty($data['indirizzo_sede'])) {
            $data['indirizzo'] = $data['indirizzo_sede'];
        }
        if (empty($data['indirizzo_sede']) && !empty($data['indirizzo'])) {
            $data['indirizzo_sede'] = $data['indirizzo'];
        }

        if (empty($data['citta']) && !empty($data['comune_sede'])) {
            $data['citta'] = $data['comune_sede'];
        }
        if (empty($data['comune_sede']) && !empty($data['citta'])) {
            $data['comune_sede'] = $data['citta'];
        }

        if (empty($data['cap']) && !empty($data['cap_sede'])) {
            $data['cap'] = $data['cap_sede'];
        }
        if (empty($data['cap_sede']) && !empty($data['cap'])) {
            $data['cap_sede'] = $data['cap'];
        }

        if (empty($data['interno']) && !empty($data['nr_sede'])) {
            $data['interno'] = $data['nr_sede'];
        }
        if (empty($data['nr_sede']) && !empty($data['interno'])) {
            $data['nr_sede'] = $data['interno'];
        }

        if (empty($data['telefono']) && !empty($data['cellulare'])) {
            $data['telefono'] = $data['cellulare'];
        }
        if (empty($data['cellulare']) && !empty($data['telefono'])) {
            $data['cellulare'] = $data['telefono'];
        }

        if (empty($data['denominazione']) && (!empty($data['nome']) || !empty($data['cognome']))) {
            $data['denominazione'] = trim(($data['cognome'] ?? '') . ' ' . ($data['nome'] ?? ''));
        }

        if ((empty($data['nome']) && empty($data['cognome'])) && !empty($data['denominazione'])) {
            $parts = preg_split('/\s+/', trim((string) $data['denominazione']));
            if (is_array($parts) && count($parts) > 1) {
                $data['nome'] = array_pop($parts);
                $data['cognome'] = implode(' ', $parts);
            }
        }

        return $data;
    }

    protected function setModelColumnValue($model, string $campo, $valore): void
    {
        if ($valore === null || $valore === '') {
            return;
        }
        if (!$this->modelHasColumn($model, $campo)) {
            return;
        }
        $model->{$campo} = $valore;
    }

    protected function modelHasColumn($model, string $campo): bool
    {
        static $cache = [];

        $table = $model->getTable();
        if (!isset($cache[$table])) {
            $cache[$table] = Schema::getColumnListing($table);
        }

        return in_array($campo, $cache[$table], true);
    }

    protected function resolveGestoreByCategoria(GestoreContrattoEnergia $gestoreCorrente, string $categoria): ?GestoreContrattoEnergia
    {
        $categoria = strtolower($categoria);
        if (!in_array($categoria, ['consumer', 'business'], true)) {
            return null;
        }

        $modelProdotto = (string) $gestoreCorrente->model_prodotto;
        $modelProdottoLower = strtolower($modelProdotto);

        if (!str_contains($modelProdottoLower, 'consumer') && !str_contains($modelProdottoLower, 'business')) {
            return null;
        }

        if ($categoria === 'consumer') {
            $targetProdotto = str_ireplace('business', 'Consumer', $modelProdotto);
        } else {
            $targetProdotto = str_ireplace('consumer', 'Business', $modelProdotto);
        }

        if ($targetProdotto === $modelProdotto) {
            $targetProdotto = $modelProdotto;
        }

        $gestore = GestoreContrattoEnergia::query()
            ->where('attivo', 1)
            ->where('model_prodotto', $targetProdotto)
            ->first();

        if ($gestore) {
            return $gestore;
        }

        $categoriaAttuale = $this->categoriaDaTipoProdotto($modelProdotto);
        if ($categoriaAttuale === $categoria) {
            return $gestoreCorrente;
        }

        return null;
    }

    protected function tipoFile($estensione)
    {

        switch ($estensione) {
            case 'png':
            case 'jpeg':
            case 'jpg':
                return 'immagine';

            case 'pdf':
                return 'pdf';
        }

    }


    protected function determinaPuoModificare()
    {
        return $this->currentUser()->hasAnyPermission(['admin', 'supervisore']);

    }

    protected function determinaPuoCambiareStato()
    {
        return $this->determinaPuoModificare() || $this->currentUser()->hasPermissionTo('supervisore');

    }

    protected function determinaPuoCreare()
    {
        return $this->currentUser()->hasAnyPermission(['admin', 'agente']);

    }


}

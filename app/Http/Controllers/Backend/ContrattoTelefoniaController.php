<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Funzioni\Twilio;
use App\Http\Funzioni\VisuraCameraleService;
use App\Http\MieClassi\PdfProdottoSkyGlass;
use App\Http\MieClassi\PdfProdottoSkyTv;
use App\Models\AllegatoContratto;
use App\Models\Cliente;
use App\Models\EsitoTelefonia;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\ProdottoSkyGlass;
use App\Models\ProdottoSkyTv;
use App\Models\ProdottoSkyTvWifi;
use App\Models\ProdottoSkyWifi;
use App\Models\ProdottoTimWifi;
use App\Models\ProdottoVodafoneFissa;
use App\Models\ProdottoWindtre;
use App\Models\TabMotivoKo;
use App\Models\TipoContratto;
use App\Models\User;
use App\Models\VisuraCamerale;
use App\Notifications\NotificaAdmin;
use App\Notifications\NotificaAgenteCambioEsitoContratto;
use App\Notifications\NotificaAgenteNuovoContratto;
use App\Notifications\NotificaCliente;
use App\Notifications\NotificaDatiAccessoClienteContratto;
use App\Notifications\NotificaGenericaGestore;
use App\Rules\CodiceFiscaleRule;
use App\Rules\DataItalianaRule;
use App\Rules\EmailContrattoRule;
use App\Rules\TelefonoContrattoRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContrattoTelefonia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputToUpper;

class ContrattoTelefoniaController extends Controller
{
    protected $conFiltro = false;

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }


    /**
     * Display a listing of the resource.
     *
        * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|array
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

        $puoModificare = ContrattoTelefonia::determinaPuoModificare();
        $puoCambiareStato = ContrattoTelefonia::determinaPuoCambiareStato();
        $puoCreare = ContrattoTelefonia::determinaPuoCreare();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.ContrattoTelefonia.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare,
                    'puoCambiareStato' => $puoCambiareStato,
                    'puoCreare' => $puoCreare,

                ])->render())
            ];
        }


        return view('Backend.ContrattoTelefonia.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ContrattoTelefonia::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\ContrattoTelefonia::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in codice cliente, codice contratto, nominativo, email, telefono',
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

        $queryBuilder = \App\Models\ContrattoTelefonia::query()
            ->with(['comune' => function ($q) {
                $q->select('id', 'comune', 'targa');
            }])
            ->with(['esito' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['tipoContratto.gestore' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['tipoContratto' => function ($q) {
                $q->select('id', 'gestore_id', 'nome', 'pda');
            }])
            ->with(['agente' => function ($q) {
                $q->select('id', 'alias');
            }])
            ->withCount('allegati');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',codice_cliente,codice_contratto,nome,cognome,email,telefono,iban)'), 'like', "%$t%");
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
        if ($request->has('gestore_id')) {
            $queryBuilder->whereRelation('tipoContratto', 'gestore_id', $request->input('gestore_id'));
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
        * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(Request $request)
    {


        if ($request->ajax()) {
            $record = new ContrattoTelefonia();

            if ($this->currentUser()->hasPermissionTo('agente')) {
                $record->agente_id = Auth::id();
            }
            return view('Backend.ContrattoTelefonia.modalNuovo', [
                'record' => $record,
                'controller' => get_class($this),
                'titoloPagina' => 'Nuovo contratto telefonia'
            ]);
        }

        $prodotto = null;

        if ($request->has('duplica')) {
            $record = ContrattoTelefonia::find($request->input('duplica'));
            abort_if(!$record, 404);
            $record->id = null;
            $tipoContratto = TipoContratto::find($record->tipo_contratto_id);
            $prodotto = $record->prodotto;

        } else {
            $record = new ContrattoTelefonia();
            if ($this->currentUser()->hasPermissionTo('agente')) {
                $record->agente_id = Auth::id();
            } else {
                $record->agente_id = $request->input('agente_id');
            }
            $record->tipo_contratto_id = $request->input('tipo_contratto_id');

            $tipoContratto = TipoContratto::find($record->tipo_contratto_id);
            if ($tipoContratto->prodotto) {
                $classe = 'App\Models\\' . $tipoContratto->prodotto;
                $prodotto = $this->presetCampi(new $classe(), $tipoContratto->prodotto);
            }

        }
        $record->uid = Str::ulid();
        $record->data = today();


        return view('Backend.ContrattoTelefonia.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ContrattoTelefonia::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ContrattoTelefoniaController::class, 'index']) => 'Torna a elenco ' . ContrattoTelefonia::NOME_PLURALE],
            'recordProdotto' => $prodotto,
            'tipoProdotto' => $tipoContratto->prodotto,
            'creaContratto' => !$tipoContratto->crea_in_bozza
        ]);
    }


    protected function presetCampi($record, $prodotto)
    {
        switch ($prodotto) {
            case 'ProdottoSkyTv':
                $record->tipologia_cliente = 'persona_fisica';
                $record->metodo_pagamento_tv = 'carta_credito';
                break;
        }

        return $record;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
        * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $tipoProdotto = $request->input('tipo_prodotto');
        $rules = $this->rules($request, null);
        if ($tipoProdotto) {
            $altreRules = 'rules' . $tipoProdotto;
            $rules = array_merge($rules, $this->{$altreRules}(null));
        }

        $request->validate($rules);
        $record = new ContrattoTelefonia();
        $this->salvaDati($record, $request);

        if ($record->esito_id !== 'bozza') {
            $this->inviaNotifiche($record);
        }

        if ($this->currentUser()->hasPermissionTo('agente')) {
            Notifica::notificaAdAdmin('Nuovo contratto', '<span class="fw-bold">' . $record->tipoContratto->nome . '</span> caricato da <span class="fw-bold">' . $record->agente->nominativo() . '</span> per il cliente <span class="fw-bold">' . $record->nominativo() . '</span>');
        }

        return redirect()->action([ContrattoTelefoniaController::class, 'show'], $record->id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $record = ContrattoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo contratto non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        $puoCreare = $this->currentUser()->hasAnyPermission(['admin', 'agente']);


        return view('Backend.ContrattoTelefonia.show', [
            'record' => $record,
            'controller' => ContrattoTelefoniaController::class,
            'titoloPagina' => ucfirst(ContrattoTelefonia::NOME_SINGOLARE) . ' ' . $record->nominativo(),
            'breadcrumbs' => [action([ContrattoTelefoniaController::class, 'index']) => 'Torna a elenco ' . ContrattoTelefonia::NOME_PLURALE],
            'puoCreare' => $puoCreare

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
        * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $record = ContrattoTelefonia::with('tipoContratto')->find($id);
        abort_if(!$record, 404, 'Questo contratto non esiste');
        abort_if(!$record->puoModificare($this->currentUser()->hasPermissionTo('admin')), 404, 'Non puo modificare questo contratto');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        $recordProdotto = null;
        $tipoProdotto = $record->tipoContratto->prodotto;
        if ($tipoProdotto) {
            $recordProdotto = $record->prodotto;
            if (!$recordProdotto) {
                $classe = 'App\Models\\' . $tipoProdotto;
                $recordProdotto = new $classe();

            }

        }

        return view('Backend.ContrattoTelefonia.edit', [
            'record' => $record,
            'controller' => ContrattoTelefoniaController::class,
            'titoloPagina' => 'Modifica ' . ContrattoTelefonia::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ContrattoTelefoniaController::class, 'index']) => 'Torna a elenco ' . ContrattoTelefonia::NOME_PLURALE],
            'recordProdotto' => $recordProdotto,
            'tipoProdotto' => $record->tipoContratto->prodotto,
            'creaContratto' => true

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
        * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $record = ContrattoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo ' . ContrattoTelefonia::NOME_SINGOLARE . ' non esiste');

        $tipoProdotto = $request->input('tipo_prodotto');
        $rules = $this->rules($request, $id);
        if ($tipoProdotto) {
            $altreRules = 'rules' . $tipoProdotto;
            $rules = array_merge($rules, $this->{$altreRules}(null));
        }

        $request->validate($rules);

        $request->validate($this->rules($request, $id));
        $esitoPrima = $record->esito_id;
        $this->salvaDati($record, $request);

        if ($esitoPrima == 'bozza' && $record->esito_id == 'da-gestire') {
            $this->inviaNotifiche($record);
        }


        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
        * @return array
     */
    public function destroy($id)
    {
        $record = ContrattoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo contratto non esiste');

        foreach ($record->allegati as $allegato) {
            $allegato->delete();
        }
        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ContrattoTelefoniaController::class, 'index']),
        ];
    }


    public function downloadAllegato($contrattoId, $allegatoId)
    {
        $record = AllegatoContratto::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->contratto_id != $contrattoId, 404, 'Questo allegato non esiste');
        return response()->download(Storage::path($record->path_filename), $record->filename_originale);
    }

    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoContratto();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_contratti.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->uid = $request->input('uid');
            $file->dimensione_file = $filePath->getSize();
            if ($request->input('contratto_id') > 0) {
                $file->contratto_id = $request->input('contratto_id');
            }
            $file->save();


            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoContratto::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }

    public function aggiornaStato(Request $request, $id)
    {
        $contratto = ContrattoTelefonia::withCount('allegati')->with('tipoContratto')->find($id);
        abort_if(!$contratto, 404, 'Questo contratto non esiste');

        $esitoPrima = $contratto->esito_id;

        $esito = EsitoTelefonia::find($request->input('esito_id'));
        $motivoKoprima = $contratto->motivo_ko;
        $contratto->pagato = getInputCheckbox($request->input('pagato'));
        $contratto->codice_contratto = getInputToUpper($request->input('codice_contratto'));
        $contratto->codice_cliente = getInputToUpper($request->input('codice_cliente'));
        $contratto->esito_id = $esito->id;
        $contratto->esito_finale = $esito->esito_finale;
        $contratto->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));
        if (!$contratto->data_reminder && $esito->id == 'attivo' && $contratto->tipoContratto->durata_contratto) {
            $contratto->data_reminder = now()->addMonths($contratto->tipoContratto->durata_contratto)->subDays(20);
        }
        $contratto->save();

        if ($contratto->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($contratto->motivo_ko && strlen($contratto->motivo_ko) < 70) {
                $tab = TabMotivoKo::firstOrNew(['nome' => $contratto->motivo_ko, 'tipo' => 'contratto']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$contratto]);


        if ($esitoPrima == 'bozza') {
            $this->inviaNotifiche($contratto);
        }


        if ($contratto->wasChanged(['esito_id'])) {
            $esito = EsitoTelefonia::find($contratto->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($contratto) {
                    $agente = $contratto->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaAgenteCambioEsitoContratto($contratto));
                    }
                })->afterResponse();
            }
            if ($request->input('ruolo') == 'supervisore') {
                Notifica::notificaAdAdmin('Cambio stato', 'Esito per il contratto di ' . $contratto->nominativo() . ' modificato a ' . $esito->nome);
            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.contratti';
        } else {
            $view = 'Backend.ContrattoTelefonia.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => ContrattoTelefoniaController::class,
                'puoModificare' => ContrattoTelefonia::determinaPuoModificare(),
                'puoCreare' => ContrattoTelefonia::determinaPuoCreare(),
                'puoCambiareStato' => ContrattoTelefonia::determinaPuoCambiareStato(),
            ])->render())
        ];
    }


    public function azioni($id, $azione)
    {
        $u = ContrattoTelefonia::find($id);
        if (!$u) {
            return ['success' => false, 'message' => 'Questo contratto non esiste'];
        }
        switch ($azione) {
            case 'richiedi_visura':

                if (VisuraCamerale::where('contratto_id', $u->id)->exists()) {
                    return ['success' => false, 'message' => 'Hai già richiesto una visura per questo contratto'];

                }


                $visuraService = new VisuraCameraleService();


                $res = $visuraService->richiediVisura($u->natura_giuridica, $u->partita_iva);


                if ($res) {

                    $prezzoVisura = $visuraService->calcolaPrezzo($res->data->tipo);
                    $movimento = new MovimentoPortafoglio();
                    $movimento->agente_id = Auth::id();
                    $movimento->importo = -$prezzoVisura;
                    $movimento->descrizione = 'Visura camerale ' . $res->data->tipo . ' per ' . $u->partita_iva;
                    $movimento->save();


                    $record = new VisuraCamerale();
                    $record->agente_id = Auth::id();
                    $record->contratto_id = $u->id;
                    $record->cliente_id = $u->cliente_id;
                    $record->richiesta_id = $res->data->id;
                    $record->response = $res;
                    $record->tipo = $res->data->tipo;
                    $record->stato_richiesta = $res->data->stato_richiesta;
                    $record->prezzo = $prezzoVisura;
                    $record->save();
                    return ['success' => true, 'message' => 'Visura richiesta'];

                } else {
                    return ['success' => false, 'message' => $visuraService->message];
                }


            case 'impersona':
                return $this->azioneImpersona($id);

            case 'invia-mail-password-reset':
                return $this->azioneInviaMailPassowrdReset($id);

            case 'resetta-password':
                $user = User::find($id);
                $user->password = bcrypt('123456');
                $user->save();
                return ['success' => true, 'title' => 'Password impostata', 'message' => 'La password è stata impostata a 123456'];

            default:
                return ['success' => false, 'message' => 'Azione ' . $azione . ' non esiste'];


        }

    }


    public function pda($id)
    {
        $contratto = ContrattoTelefonia::find($id);


        if ($contratto->tipoContratto->pda) {
            //Allega PDA
            $classe = 'App\Http\MieClassi\Pdf' . $contratto->tipoContratto->pda;
            $pdf = new $classe();
            $pdf->generaPdf($contratto);
            return $pdf->render();
        }

    }


    /**
     * @param ContrattoTelefonia $contratto
     * @return void
     */
    public function inviaNotifiche($contratto)
    {


        $this->creaUtente($contratto);


        dispatch(function () use ($contratto) {
            //Notifica ad agente
            $user = $contratto->agente;
            try {
                $user->notify(new NotificaAgenteNuovoContratto($contratto));

            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'ad agente per il contratto di ' . $contratto->nominativo() . ': ' . $exception->getMessage(), 'error');
            }

        })->afterResponse();

        //Notifiche al gestore
        $emailGestore = $contratto->tipoContratto->email_notifica_gestore;
        if (!$emailGestore) {
            $emailGestore = $contratto->tipoContratto->gestore->email_notifica_a_gestore;
        }
        if ($emailGestore) {
            dispatch(function () use ($contratto, $emailGestore) {
                foreach (explode(';', $emailGestore) as $email) {
                    try {
                        Notification::route('mail', $email)->notify(new NotificaGenericaGestore($contratto));
                    } catch (\Exception $exception) {
                        report($exception);
                        Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $email . ' per il contratto di ' . $contratto->nominativo() . ': ' . $exception->getMessage(), 'error');
                    }
                }
            })->afterResponse();
        }

        if ($this->currentUser()->hasPermissionTo('agente')) {
            dispatch(function () use ($contratto) {
                $user = new User();
                $user->email = 'noreply@gestiio.it';
                try {
                    $user->notify(new NotificaAdmin($contratto));

                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il contratto di ' . $contratto->nominativo() . ': ' . $exception->getMessage(), 'error');

                }
            })->afterResponse();

        }

    }

    /**
     * @param ContrattoTelefonia $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->esito_id = 'da-gestire';
            $model->caricato_da_user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'codice_cliente' => '',
            'codice_contratto' => '',
            'agente_id' => '',
            'tipo_contratto_id' => '',
            'codice_fiscale' => 'strtoupper',
            'partita_iva' => 'strtoupper',
            'ragione_sociale' => 'app\getInputUcwords',
            'natura_giuridica' => '',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'telefono' => 'app\getInputTelefono',
            'indirizzo' => 'app\getInputUcwords',
            'civico' => 'app\getInputUcwords',
            'citta' => '',
            'cap' => '',
            'nome_citofono' => '',
            'scala' => '',
            'piano' => '',
            'note' => '',
            'cittadinanza' => '',
            'uid' => '',
            'iban' => 'strtoupper',
            'carta_di_credito' => '',
            'carta_di_credito_cvc' => '',
            'carta_di_credito_scadenza' => '',

            'tipo_documento' => '',
            'numero_documento' => 'strtoupper',
            'rilasciato_da' => '',
            'comune_rilascio' => '',
            'data_rilascio' => 'App\getInputData',
            'data_scadenza' => 'App\getInputData',
            'permesso_soggiorno_numero' => '',
            'permesso_soggiorno_scadenza' => 'App\getInputData',
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

        $model->cliente_id = $this->salvaDatiCliente($cliente, $model);

        if ($request->input('bozza')) {
            $model->esito_id = 'bozza';
        } elseif ($model->esito_id == 'bozza') {
            $model->esito_id = 'da-gestire';
        }

        $model->save();


        AllegatoContratto::where('uid', $model->uid)->whereNull('contratto_id')->update(['contratto_id' => $model->id, 'uid' => null]);


        $this->salvaDatiProdotti($model, $request);

        return $model;
    }


    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return void
     */
    protected function salvaDatiProdotti($ordineModel, $request)
    {
        $tipoContratto = TipoContratto::find($ordineModel->tipo_contratto_id);
        if ($tipoContratto->prodotto) {
            $classe = 'salvaDati' . $tipoContratto->prodotto;
            $this->$classe($ordineModel, $request);
        }


    }

    /**
     * @param ContrattoTelefonia $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoSkyGlass($ordineModel, $request)
    {
        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoSkyGlass();
        }

        //Ciclo su campi
        $campi = [
            'dimensione' => '',
            'colore_sky_glass' => '',
            'accessori' => 'app\getInputCheckbox',
            'colore_front_cover' => '',
            'sky_stream' => '',
            'installazione_a_muro' => 'app\getInputCheckbox',
            'pacchetti_sky' => '',
            'pacchetti_netflix' => '',
            'servizi_opzionali' => '',
            'frequenza_pagamento_sky_glass' => '',
            'metodo_pagamento_sky_glass' => '',
            'metodo_pagamento_tv' => '',
            'tipologia_cliente' => '',
        ];

        $model->contratto_id = $ordineModel->id;
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

    }


    /**
     * @param Cliente $model
     * @param ContrattoTelefonia $request
     * @return mixed
     */
    protected function salvaDatiCliente($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'codice_fiscale' => 'strtoupper',
            'partita_iva' => 'strtoupper',
            'natura_giuridica' => '',
            'ragione_sociale' => 'app\getInputUcwords',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'telefono' => '',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $model->$campo = $request->$campo;
        }

        $model->save();


        return $model->id;
    }

    /**
     * @param ContrattoTelefonia $contratto
        * @return void
     */
    protected function creaUtente(ContrattoTelefonia $contratto)
    {
        $cliente = Cliente::find($contratto->cliente_id);

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

            dispatch(function () use ($contratto, $password, $user) {
                //Notifica a cliente
                try {
                    $user->notify(new NotificaDatiAccessoClienteContratto($contratto, $password));
                    $user->invio_dati_accesso = now();
                    $user->save();
                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio dati accesso cliente', 'a ' . $user->nominativo() . ': ' . $exception->getMessage(), 'error');

                }
            })->afterResponse();

        } else {
            dispatch(function () use ($contratto, $user) {
                //Notifica a cliente
                $user->notify(new NotificaCliente($contratto));
            })->afterResponse();

        }


    }

    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoSkyTv($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoSkyTv();
        }

        //Ciclo su campi
        $campi = [
            'codice_cliente' => '',
            'profilo' => '',
            'codice_promozione' => '',
            'tipologia_cliente' => '',
            'pacchetti_sky' => '',
            'servizi_opzionali' => '',
            'offerte_sky' => '',
            'canali_opzionali' => '',
            'servizio_decoder' => '',
            'tecnologia' => '',
            'sky_q_mini' => '',
            'solo_smartcard' => 'app\getInputCheckbox',
            'matricola_smartcard' => '',
            'metodo_pagamento_tv' => '',
            'frequenza_pagamento_tv' => '',
            'carta_di_credito_tipo' => '',
            'carta_di_credito_numero' => '',
            'carta_di_credito_cognome' => '',
            'carta_di_credito_nome' => '',
            'carta_di_credito_valida_al' => '',
            'sepa_banca' => '',
            'sepa_agenzia' => '',
            'sepa_iban' => '',
            'sepa_intestatario' => '',
            'sepa_via' => '',
            'sepa_codice_fiscale' => '',
            'consenso_1' => 'app\getInputCheckbox',
            'consenso_2' => 'app\getInputCheckbox',
            'consenso_3' => 'app\getInputCheckbox',
            'consenso_4' => 'app\getInputCheckbox',
            'consenso_5' => 'app\getInputCheckbox',
            'consenso_6' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }
        return $model;
    }

    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoSkyWifi($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoSkyWifi();
        }

        //Ciclo su campi
        $campi = [
            'codice_cliente' => '',
            'tipologia_cliente' => '',
            'offerta' => '',
            'modem_wifi_hub' => 'app\getInputCheckbox',
            'ultra_wifi' => 'app\getInputCheckbox',
            'wifi_spot' => 'app\getInputNumeroZero',
            'pacchetti_voce' => '',
            'linea_telefonica' => '',
            'numero_da_migrare' => '',
            'codice_migrazione_voce' => '',
            'codice_migrazione_dati' => '',

            'metodo_pagamento_internet' => '',

            'carta_di_credito_tipo' => '',
            'carta_di_credito_numero' => '',
            'carta_di_credito_cognome' => '',
            'carta_di_credito_nome' => '',
            'carta_di_credito_valida_al' => '',
            'sepa_banca' => '',
            'sepa_agenzia' => '',
            'sepa_iban' => '',
            'sepa_intestatario' => '',
            'sepa_via' => '',
            'sepa_codice_fiscale' => '',
            'consenso_1' => 'app\getInputCheckbox',
            'consenso_2' => 'app\getInputCheckbox',
            'consenso_3' => 'app\getInputCheckbox',
            'consenso_4' => 'app\getInputCheckbox',
            'consenso_5' => 'app\getInputCheckbox',
            'consenso_6' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }
        return $model;
    }


    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoSkyTvWifi($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoSkyTvWifi();
        }

        //Ciclo su campi
        $campi = [
            'codice_cliente' => '',
            'profilo' => '',
            'codice_promozione' => '',
            'tipologia_cliente' => '',
            'pacchetti_sky' => '',
            'servizi_opzionali' => '',
            'offerte_sky' => '',
            'canali_opzionali' => '',
            'servizio_decoder' => '',
            'tecnologia' => '',
            'sky_q_mini' => '',
            'solo_smartcard' => 'app\getInputCheckbox',
            'matricola_smartcard' => '',
            'metodo_pagamento_tv' => '',
            'frequenza_pagamento_tv' => '',
            'carta_di_credito_tipo' => '',
            'carta_di_credito_numero' => '',
            'carta_di_credito_cognome' => '',
            'carta_di_credito_nome' => '',
            'carta_di_credito_valida_al' => '',
            'sepa_banca' => '',
            'sepa_agenzia' => '',
            'sepa_iban' => '',
            'sepa_intestatario' => '',
            'sepa_via' => '',
            'sepa_codice_fiscale' => '',
            'consenso_1' => 'app\getInputCheckbox',
            'consenso_2' => 'app\getInputCheckbox',
            'consenso_3' => 'app\getInputCheckbox',
            'consenso_4' => 'app\getInputCheckbox',
            'consenso_5' => 'app\getInputCheckbox',
            'consenso_6' => 'app\getInputCheckbox',

            //Wifi
            'offerta' => '',
            'modem_wifi_hub' => 'app\getInputCheckbox',
            'ultra_wifi' => 'app\getInputCheckbox',
            'wifi_spot' => 'app\getInputNumeroZero',
            'pacchetti_voce' => '',
            'linea_telefonica' => '',
            'numero_da_migrare' => '',
            'codice_migrazione_voce' => '',
            'codice_migrazione_dati' => '',
            'metodo_pagamento_internet' => '',

        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }
        return $model;
    }


    /**
     * @param ProdottoTimWifi $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoTimWifi($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoTimWifi();
        }

        //Ciclo su campi
        $campi = [
            'citta_di_nascita' => '',
            'provincia_di_nascita' => '',
            'nazionalita' => '',
            'indirizzo_impianto' => '',
            'civico_impianto' => '',
            'piano_impianto' => '',
            'scala_impianto' => '',
            'interno_impianto' => '',
            'citofono_impianto' => '',
            'localita_impianto' => '',
            'indirizzo_fattura' => '',
            'civico_fattura' => '',
            'citta_fattura' => '',
            'cap_fattura' => '',
            'indirizzo_timcard' => '',
            'civico_timcard' => '',
            'citta_timcard' => '',
            'cap_timcard' => '',
            'numero_cellulare' => '',
            'recapito_alternativo' => '',
            'firmatario_nome_cognome' => '',
            'firmatario_indirizzo_completo' => '',
            'firmatario_tipo_documento' => '',
            'firmatario_rilasciato_da' => '',
            'firmatario_data_emissione' => 'app\getInputData',
            'firmatario_data_scadenza' => 'app\getInputData',
            'la_tua_linea_di_casa' => '',
            'variazione_numero' => '',
            'linea_mobile_tim' => 'app\getInputCheckbox',
            'linea_mobile_new' => 'app\getInputCheckbox',
            'linea_mobile_abbonamento' => 'app\getInputCheckbox',
            'linea_mobile_prepagato' => 'app\getInputCheckbox',
            'linea_mobile_operatore' => '',
            'linea_mobile_abbinata_offerta' => '',
            'linea_mobile_cf_piva_attuale' => '',
            'linea_mobile_numero_seriale' => '',
            'linea_mobile_trasferimento_credito' => 'app\getInputCheckbox',
            'la_tua_offerta' => '',
            'opzione_inclusa' => '',
            'qualora' => 'app\getInputCheckbox',
            'modem_tim' => '',
            'offerta_scelta' => '',
            'codice_migrazione' => '',
            'numero_telefonico' => '',
            'pagamento_bollettino' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();
        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }
        return $model;
    }


    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoVodafoneFissa($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoVodafoneFissa();
        }

        //Ciclo su campi
        $campi = [
            'offerta' => '',
            'tecnologia' => '',
            'metodo_pagamento' => '',
            'numero_da_migrare' => '',
            'gestore_linea_esistente' => '',
            'codice_migrazione' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();
        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

        return $model;
    }

    /**
     * @param ContrattoTelefonia $ordineModel
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiProdottoWindtre($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoWindtre();
        }

        //Ciclo su campi
        $campi = [
            'opzioni' => '',

        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();
        if ($nuovo) {
            $ordineModel->prodotto_id = $model->contratto_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

        return $model;
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
        return \App\Models\ContrattoTelefonia::get();
    }


    protected function rules($request, $id = null)
    {

        $rules = [
            'data' => ['required', new DataItalianaRule()],
            'codice_cliente' => ['nullable', 'max:255'],
            'codice_contratto' => ['nullable', 'max:255'],
            'agente_id' => ['required'],
            'tipo_contratto_id' => ['required'],
            'codice_fiscale' => ['required', new CodiceFiscaleRule()],
            'ragione_sociale' => ['nullable', 'max:255'],
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', new EmailContrattoRule($request->input('tipo_contratto_id'), $id)],
            'telefono' => ['required', new \App\Rules\TelefonoRule(), new TelefonoContrattoRule($request->input('tipo_contratto_id'), $id)],
            'indirizzo' => ['required', 'max:255'],
            'citta' => ['required', 'max:255'],
            'cap' => ['required'],
            'carta_di_credito_scadenza' => ['nullable', 'max:5'],
            'carta_di_credito_cvc' => ['nullable', 'max:3'],
        ];

        return $rules;
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

    protected function rulesProdottoSkyGlass($id = null)
    {


        $rules = [
            'tipologia_cliente' => ['required'],
            'dimensione' => ['required', 'max:255'],
            'colore_sky_glass' => ['required', 'max:255'],
            'accessori' => ['nullable'],
            'colore_front_cover' => ['nullable', 'max:255'],
            'sky_stream' => ['nullable', 'max:255'],
            'installazione_a_muro' => ['nullable'],
            'pacchetti_sky' => ['nullable'],
            'pacchetti_netflix' => ['required'],
            'servizi_opzionali' => ['nullable'],
            'frequenza_pagamento_sky_glass' => ['required', 'max:255'],
            'metodo_pagamento_sky_glass' => ['nullable', 'max:255'],
            'metodo_pagamento_tv' => ['nullable', 'max:255'],
            'tipo_documento' => ['required'],
            'numero_documento' => ['required'],
            'rilasciato_da' => ['required'],
            'data_rilascio' => ['required', new DataItalianaRule()],
            'data_scadenza' => ['required', new DataItalianaRule()],
        ];

        return $rules;
    }

    protected function rulesProdottoSkyTv($id = null)
    {


        $rules = [
            'codice_cliente' => ['nullable', 'max:255'],
            'profilo' => ['required', 'max:255'],
            'codice_promozione' => ['nullable', 'max:255'],
            'tipologia_cliente' => ['required', 'max:255'],
            'pacchetti_sky' => ['nullable'],
            'servizi_opzionali' => ['nullable'],
            'offerte_sky' => ['nullable'],
            'canali_opzionali' => ['nullable'],
            'servizio_decoder' => ['nullable'],
            'tecnologia' => ['nullable', 'max:255'],
            'sky_q_mini' => ['nullable'],
            'solo_smartcard' => ['nullable'],
            'matricola_smartcard' => ['nullable', 'max:255'],
            'metodo_pagamento_tv' => ['nullable', 'max:255'],
            'frequenza_pagamento_tv' => ['nullable', 'max:255'],
            'carta_di_credito_tipo' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_numero' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_cognome' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_nome' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_valida_al' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'sepa_banca' => ['nullable', 'max:255'],
            'sepa_agenzia' => ['nullable', 'max:255'],
            'sepa_iban' => ['nullable', 'max:255'],
            'sepa_intestatario' => ['nullable', 'max:255'],
            'sepa_via' => ['nullable', 'max:255'],
            'sepa_codice_fiscale' => ['nullable', 'max:255'],
            'consenso_1' => ['nullable'],
            'consenso_2' => ['nullable'],
            'consenso_3' => ['nullable'],
            'consenso_4' => ['nullable'],
            'consenso_5' => ['nullable'],
            'consenso_6' => ['nullable'],
            'tipo_documento' => ['required'],
            'numero_documento' => ['required'],
            'rilasciato_da' => ['required'],
            'data_rilascio' => ['required', new DataItalianaRule()],
            'data_scadenza' => ['required', new DataItalianaRule()],

        ];

        return $rules;
    }

    protected function rulesProdottoSkyWifi($id = null)
    {


        $rules = [
            'codice_cliente' => ['nullable', 'max:255'],
            'codice_promozione' => ['nullable', 'max:255'],
            'tipologia_cliente' => ['required', 'max:255'],
            'offerta' => ['required'],
            'carta_di_credito_tipo' => ['required_if:metodo_pagamento_internet,carta_credito', 'max:255'],
            'carta_di_credito_numero' => ['required_if:metodo_pagamento_internet,carta_credito', 'max:255'],
            'carta_di_credito_cognome' => ['required_if:metodo_pagamento_internet,carta_credito', 'max:255'],
            'carta_di_credito_nome' => ['required_if:metodo_pagamento_internet,carta_credito', 'max:255'],
            'carta_di_credito_valida_al' => ['required_if:metodo_pagamento_internet,carta_credito', 'max:255'],
            'sepa_banca' => ['nullable', 'max:255'],
            'sepa_agenzia' => ['nullable', 'max:255'],
            'sepa_iban' => ['nullable', 'max:255'],
            'sepa_intestatario' => ['nullable', 'max:255'],
            'sepa_via' => ['nullable', 'max:255'],
            'sepa_codice_fiscale' => ['nullable', 'max:255'],
            'consenso_1' => ['nullable'],
            'consenso_2' => ['nullable'],
            'consenso_3' => ['nullable'],
            'consenso_4' => ['nullable'],
            'consenso_5' => ['nullable'],
            'consenso_6' => ['nullable'],
            'tipo_documento' => ['required'],
            'numero_documento' => ['required'],
            'rilasciato_da' => ['required'],
            'data_rilascio' => ['required', new DataItalianaRule()],
            'data_scadenza' => ['required', new DataItalianaRule()],

        ];

        return $rules;
    }

    protected function rulesProdottoSkyTvWifi($id = null)
    {


        $rules = [
            'codice_cliente' => ['nullable', 'max:255'],
            'profilo' => ['required', 'max:255'],
            'codice_promozione' => ['nullable', 'max:255'],
            'tipologia_cliente' => ['required', 'max:255'],
            'pacchetti_sky' => ['nullable'],
            'servizi_opzionali' => ['nullable'],
            'offerte_sky' => ['nullable'],
            'canali_opzionali' => ['nullable'],
            'servizio_decoder' => ['nullable'],
            'tecnologia' => ['nullable', 'max:255'],
            'sky_q_mini' => ['nullable'],
            'solo_smartcard' => ['nullable'],
            'matricola_smartcard' => ['nullable', 'max:255'],
            'metodo_pagamento_tv' => ['nullable', 'max:255'],
            'frequenza_pagamento_tv' => ['nullable', 'max:255'],
            'carta_di_credito_tipo' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_numero' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_cognome' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_nome' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'carta_di_credito_valida_al' => ['required_if:metodo_pagamento_tv,carta_credito', 'max:255'],
            'sepa_banca' => ['nullable', 'max:255'],
            'sepa_agenzia' => ['nullable', 'max:255'],
            'sepa_iban' => ['nullable', 'max:255'],
            'sepa_intestatario' => ['nullable', 'max:255'],
            'sepa_via' => ['nullable', 'max:255'],
            'sepa_codice_fiscale' => ['nullable', 'max:255'],
            'consenso_1' => ['nullable'],
            'consenso_2' => ['nullable'],
            'consenso_3' => ['nullable'],
            'consenso_4' => ['nullable'],
            'consenso_5' => ['nullable'],
            'consenso_6' => ['nullable'],
            'offerta' => ['required'],

            'tipo_documento' => ['required'],
            'numero_documento' => ['required'],
            'rilasciato_da' => ['required'],
            'data_rilascio' => ['required', new DataItalianaRule()],
            'data_scadenza' => ['required', new DataItalianaRule()],
        ];

        return $rules;
    }

    protected function rulesProdottoVodafoneFissa($id = null)
    {
        $rules = [
        ];
        return $rules;
    }

    protected function rulesProdottoTimWifi($id = null)
    {


        $rules = [
            'indirizzo_fattura' => ['nullable', 'max:255'],
            'citta_fattura' => ['nullable', 'max:255'],
            'cap_fattura' => ['nullable', 'max:255'],
            'indirizzo_timcard' => ['nullable', 'max:255'],
            'citta_timcard' => ['nullable', 'max:255'],
            'cap_timcard' => ['nullable', 'max:255'],
            'la_tua_linea_di_casa' => ['required', 'max:255'],
            'variazione_numero' => ['nullable', 'max:255'],
            'linea_mobile_tim' => ['nullable'],
            'linea_mobile_new' => ['nullable'],
            'linea_mobile_abbonamento' => ['nullable'],
            'linea_mobile_prepagato' => ['nullable'],
            'linea_mobile_operatore' => ['nullable', 'max:255'],
            'linea_mobile_abbinata_offerta' => ['nullable', 'max:255'],
            'linea_mobile_cf_piva_attuale' => ['nullable', 'max:255'],
            'linea_mobile_numero_seriale' => ['nullable', 'max:255'],
            'linea_mobile_trasferimento_credito' => ['nullable'],
            'la_tua_offerta' => ['required', 'max:255'],
            'opzione_inclusa' => ['nullable', 'max:255'],
            'qualora' => ['nullable'],
            'modem_tim' => ['nullable', 'max:255'],
            'offerta_scelta' => ['nullable', 'max:255'],
            'cognome_nome_debitore' => ['nullable', 'max:255'],
            'codice_fiscale_debitore' => ['nullable', 'max:255'],
            'iban_debitore' => ['nullable', 'max:255'],
        ];

        return $rules;
    }


    protected function rulesProdottoWindtre($id = null)
    {
        return [];
    }


}

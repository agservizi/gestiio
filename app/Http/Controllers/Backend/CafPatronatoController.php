<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\AllegatoCafPatronato;
use App\Models\Cliente;
use App\Models\EsitoCafPatronato;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\TabMotivoKo;
use App\Models\TipoCafPatronato;
use App\Models\User;
use App\Notifications\NotificaAgenteCambioEsitoContratto;
use App\Notifications\NotificaCafPatronato;
use App\Notifications\NotificaCafPatronatoACliente;
use App\Notifications\NotificaCafPatronatoAdAdmin;
use App\Notifications\NotificaCafPatronatoCambioEsitoAdAgente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CafPatronato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputToUpper;
use function App\importo;
use function App\siNo;

class CafPatronatoController extends Controller
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
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);
        $giorniFermo = max(1, (int)$request->input('giorni_fermo', 7));

        $ordinamenti = [
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

        $kpiInLavorazione = (clone $recordsQB)
            ->whereIn('esito_id', ['bozza', 'da-gestire'])
            ->count();

        $kpiBloccate = (clone $recordsQB)
            ->whereNotNull('motivo_ko')
            ->where('motivo_ko', '!=', '')
            ->count();

        $kpiInScadenza = (clone $recordsQB)
            ->whereIn('esito_id', ['bozza', 'da-gestire'])
            ->whereDate('data', '>=', today())
            ->whereDate('data', '<=', today()->addDays(7))
            ->count();

        $kpiConcluse = (clone $recordsQB)
            ->whereNotIn('esito_id', ['bozza', 'da-gestire'])
            ->count();

        $praticheFermiCount = (clone $recordsQB)
            ->whereIn('esito_id', ['bozza', 'da-gestire'])
            ->whereDate('created_at', '<=', now()->subDays($giorniFermo))
            ->count();

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        $puoModificare = CafPatronato::puoModificare();
        $puoModificareEsito = CafPatronato::puoModificareEsito();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.CafPatronato.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare,
                    'puoModificareEsito' => $puoModificareEsito,
                ])->render())
            ];
        }

        if ($this->currentUser()->hasAnyPermission(['admin', 'agente', 'operatore', 'supervisore'])) {
            $testoNuovo = 'Nuova ' . \App\Models\CafPatronato::NOME_SINGOLARE;
        } else {
            $testoNuovo = null;
        }


        return view('Backend.CafPatronato.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\CafPatronato::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => $testoNuovo,
            'testoCerca' => 'Cerca in nominativo, codice fiscale',
            'puoModificare' => $puoModificare,
            'puoModificareEsito' => $puoModificareEsito,
            'giorniFermo' => $giorniFermo,
            'praticheFermiCount' => $praticheFermiCount,
            'kpiInLavorazione' => $kpiInLavorazione,
            'kpiBloccate' => $kpiBloccate,
            'kpiInScadenza' => $kpiInScadenza,
            'kpiConcluse' => $kpiConcluse,

        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\CafPatronato::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente');

        if ($request->input('giorno') && is_numeric($request->input('giorno')) && $request->input('mese') && $request->input('anno')) {
            $this->conFiltro = true;
            $data = Carbon::createFromDate($request->input('anno'), $request->input('mese'), $request->input('giorno'));
            $queryBuilder->whereDate('data', '=', $data);
        } elseif ($request->input('giorno') && $request->input('mese')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate(null, $request->input('mese'), $request->input('giorno'));
            $queryBuilder->whereDate('data', '=', $dataDa);
        } elseif ($request->input('mese') && $request->input('anno')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate($request->input('anno'), $request->input('mese'), 1);
            $dataA = $dataDa->copy()->endOfMonth();
            $queryBuilder->whereDate('data', '>=', $dataDa)->whereDate('data', '<=', $dataA);
        } elseif ($request->input('mese')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate(Carbon::today()->year, $request->input('mese'), 1);
            $dataA = $dataDa->copy()->endOfMonth();
            $queryBuilder->whereDate('data', '>=', $dataDa)->whereDate('data', '<=', $dataA);
        } elseif ($request->input('anno')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate($request->input('anno'), 1, 1);
            $dataA = $dataDa->copy()->endOfYear();
            $queryBuilder->whereDate('data', '>=', $dataDa)->whereDate('data', '<=', $dataA);
        } elseif ($request->input('giorno')) {
            $this->conFiltro = true;
            $dataDa = Carbon::createFromDate(null, null, $request->input('giorno'));
            $dataA = $dataDa->copy()->endOfYear();
            $queryBuilder->whereDate('data', '=', $dataDa)->whereDate('data', '<=', $dataA);
        }


        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw("concat_ws(' ',id,nome,cognome,codice_fiscale,email,cellulare)"), 'like', "%$t%");
            }
            $this->conFiltro = true;
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

        if ($request->filled('tipo_caf_patronato_id')) {
            $queryBuilder->where('tipo_caf_patronato_id', $request->input('tipo_caf_patronato_id'));
            $this->conFiltro = true;
        }

        if ($request->filled('agente_id')) {
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create($servizio = null)
    {
        if (!$servizio) {
            $portafoglioServizi = $this->currentUser()->agente->portafoglio_servizi;
            return view('Backend.CafPatronato.create', [
                'record' => new CafPatronato(),
                'titoloPagina' => 'Nuova pratica Caf / Patronato',
                'portafoglioServizi'=>$portafoglioServizi,
                'controller' => get_class($this),
                'breadcrumbs' => [action([CafPatronatoController::class, 'index']) => 'Torna a elenco ' . CafPatronato::NOME_PLURALE]
            ]);
        }
        $record = new CafPatronato();
        $record->data = today();
        $record->uid = Str::ulid();

        if ($this->currentUser()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }


        $tipoCafPatronato = TipoCafPatronato::find($servizio);

        return view('Backend.CafPatronato.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova pratica ' . $tipoCafPatronato->nome,
            'controller' => get_class($this),
            'breadcrumbs' => [action([CafPatronatoController::class, 'index']) => 'Torna a elenco ' . CafPatronato::NOME_PLURALE],
            'tipoCafPatronato' => $tipoCafPatronato,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
        * @return mixed
     */
    public function store(Request $request)
    {
        $servizio = $request->input('tipo_servizio');

        $request->validate($this->rules(null));
        DB::beginTransaction();
        $tipoCafPatronato = TipoCafPatronato::find($servizio);
        $record = new CafPatronato();
        $record->esito_id = 'da-gestire';
        $record->prezzo_pratica = $tipoCafPatronato->prezzo_agente;
        $record->tipo_caf_patronato_id = $tipoCafPatronato->id;

        $this->salvaDati($record, $request);

        if ($tipoCafPatronato->model) {
            $func = 'salvaDati' . $tipoCafPatronato->model;
            $this->$func($record, $request);
        }

        $movimento = new MovimentoPortafoglio();
        $movimento->agente_id = Auth::id();
        $movimento->importo = -$tipoCafPatronato->prezzo_agente;
        $movimento->descrizione = 'Pratica ' . $tipoCafPatronato->nome . ' per ' . $record->nominativo();
        $movimento->prodotto_id = $record->id;
        $movimento->prodotto_type = get_class($record);
        $movimento->portafoglio = TipiPortafoglioEnum::SERVIZI->value;
        $movimento->save();

        DB::commit();

        $this->inviaNotifiche($record);

        if ($this->currentUser()->hasPermissionTo('agente')) {
            Notifica::notificaAdAdmin('Nuova ' . CafPatronato::NOME_SINGOLARE, '<span class="fw-bold">' . $tipoCafPatronato->nome . '</span> caricato da <span class="fw-bold">' . $record->agente->nominativo() . '</span> per il cliente <span class="fw-bold">' . $record->nominativo() . '</span>');
        }

        $alertMessage = new AlertMessage();
        $alertMessage->messaggio('Ti è stato scalato l\'importo di ' . importo($tipoCafPatronato->prezzo_agente) . ' per la pratica ' . $tipoCafPatronato->nome, 'primary')->titolo('Portafoglio aggiornato', 'primary')->flash();

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function show($id)
    {
        $record = CafPatronato::find($id);
        abort_if(!$record, 404, 'Questo cafpatronato non esiste');
        return view('Backend.CafPatronato.show', [
            'record' => $record,
            'controller' => CafPatronatoController::class,
            'titoloPagina' => CafPatronato::NOME_SINGOLARE,
            'breadcrumbs' => [action([CafPatronatoController::class, 'index']) => 'Torna a elenco ' . CafPatronato::NOME_PLURALE]

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
        $record = CafPatronato::find($id);
        abort_if(!$record, 404, 'Questa pratica non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.CafPatronato.edit', [
            'record' => $record,
            'controller' => CafPatronatoController::class,
            'titoloPagina' => 'Modifica pratica ' . $record->tipo->nome,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([CafPatronatoController::class, 'index']) => 'Torna a elenco ' . CafPatronato::NOME_PLURALE],
            'tipoServizio' => $record->tipoProdotto(),
            'recordProdotto' => $record->prodotto,
            'tipoCafPatronato' => TipoCafPatronato::find($record->tipo_caf_patronato_id),

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
        $record = CafPatronato::find($id);
        abort_if(!$record, 404, 'Questo ' . CafPatronato::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

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
        $record = CafPatronato::find($id);
        abort_if(!$record, 404, 'Questo cafpatronato non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([CafPatronatoController::class, 'index']),
        ];
    }

    public function aggiornaStato(Request $request, $id)
    {
        $cafPatronato = CafPatronato::withCount('allegati')->withCount('allegatiPerCliente')->find($id);
        abort_if(!$cafPatronato, 404, 'Questo servizio non esiste');


        if ($cafPatronato->allegati_per_cliente_count == 0 && $request->input('esito_id') == 'pronto') {
            $request->validate(['allegato' => ['required']], [
                'allegato.required' => ['Manca file cliente']
            ]);
        }


        $esitoPrima = $cafPatronato->esito_id;

        $esito = EsitoCafPatronato::find($request->input('esito_id'));
        $motivoKoprima = $cafPatronato->motivo_ko;
        $cafPatronato->esito_id = $esito->id;
        $cafPatronato->esito_finale = $esito->esito_finale;
        $cafPatronato->pagato = getInputCheckbox($request->input('pagato'));
        $cafPatronato->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));

        $cafPatronato->save();

        if ($cafPatronato->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($cafPatronato->motivo_ko && strlen($cafPatronato->motivo_ko) < 70) {

                $tab = TabMotivoKo::firstOrNew(['nome' => $cafPatronato->motivo_ko, 'tipo' => 'caf-patronato']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$cafPatronato]);


        if ($cafPatronato->wasChanged(['esito_id'])) {
            $esito = EsitoCafPatronato::find($cafPatronato->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($cafPatronato) {
                    $agente = $cafPatronato->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaCafPatronatoCambioEsitoAdAgente($cafPatronato));
                    }

                })->afterResponse();

            }
            if ($this->currentUser()->hasPermissionTo('supervisore')) {
                Notifica::notificaAdAdmin('Cambio esito pratica', 'Esito per la pratica ' . $cafPatronato->nominativo() . ' modificato a ' . $esito->nome);
            }

            Log::debug('$cafPatronato->email:' . $cafPatronato->email . ' $esitoPrima !== \'pronto\':' . siNo($esitoPrima !== 'pronto') . ' $cafPatronato->esito_id == \'pronto\':' . siNo($cafPatronato->esito_id == 'pronto'));
            if ($cafPatronato->email && $esitoPrima !== 'pronto' && $cafPatronato->esito_id == 'pronto') {
                Log::debug('Invio mail NotificaCafPatronatoACliente');
                dispatch(function () use ($cafPatronato) {
                    Notification::route('mail', $cafPatronato->email)->notify(new NotificaCafPatronatoACliente($cafPatronato));
                })->afterResponse();
            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.servizi';
        } else {
            $view = 'Backend.CafPatronato.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => CafPatronatoController::class,
                'puoModificare' => CafPatronato::puoModificare(),
                'puoModificareEsito' => CafPatronato::puoModificareEsito(),

            ])->render())
        ];
    }


    public function downloadAllegato($contrattoId, $allegatoId)
    {

        $record = AllegatoCafPatronato::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->caf_patronato_id != $contrattoId, 404, 'Questo allegato non esiste');

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

    public function downloadAllegatoCliente($contrattoId)
    {

        $rcaf = CafPatronato::find($contrattoId);
        abort_if(!$rcaf, 404);
        $record = AllegatoCafPatronato::firstWhere(['caf_patronato_id' => $contrattoId, 'per_cliente' => 1]);
        abort_if(!$record, 404, 'Questo allegato non esiste');

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
        $file = new AllegatoCafPatronato();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_contratti.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->mime_type = $filePath->getMimeType();
            $contenuto = file_get_contents($filePath->getRealPath());
            $file->file_contenuto_base64 = $contenuto !== false ? base64_encode($contenuto) : null;
            if ($request->input('uid') && $request->input('uid') !== 'undefined') {
                $file->uid = $request->input('uid');
            }
            $file->dimensione_file = $filePath->getSize();
            $file->caf_patronato_id = $request->input('caf_patronato_id');
            $file->per_cliente = $request->input('per_cliente', 0);
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoCafPatronato::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }


    /**
     * @param CafPatronato $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->caricato_da_user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'cellulare' => 'app\getInputTelefono',
            'codice_fiscale' => 'strtoupper',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'note' => '',
            'uid' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '' && is_callable($funzione)) {
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


        $model->save();

        AllegatoCafPatronato::where('uid', $model->uid)->whereNull('caf_patronato_id')->update(['caf_patronato_id' => $model->id, 'uid' => null]);

        return $model;
    }


    /**
     * @param Cliente $model
     * @param CafPatronato $request
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
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $model->$campo = $request->$campo;
        }

        $model->telefono = $request->cellulare;

        $model->save();


        return $model->id;
    }


    /**
     * @param mixed $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiCafPatIsee($ordineModel, $request)
    {
        $model = $ordineModel->prodotto;
        if (!$model) {
            return null;
        }

        $model->save();

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
        return \App\Models\CafPatronato::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['nullable', 'max:255', 'email'],
            'cellulare' => ['required', 'max:255'],
            'codice_fiscale' => ['required', new \App\Rules\CodiceFiscaleRule()],
            'cliente_id' => ['nullable'],
            'indirizzo' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'cap' => ['nullable'],
            'note' => ['nullable'],
            'esito_finale' => ['nullable', 'max:255'],
            'mese_pagamento' => ['nullable'],
            'motivo_ko' => ['nullable', 'max:255'],
            'pagato' => ['nullable'],
            'prodotto_id' => ['nullable'],
            'prodotto_type' => ['nullable', 'max:255'],
        ];

        return $rules;
    }


    /**
     * @param CafPatronato $cafPatronato
     * @return void
     */
    public function inviaNotifiche($cafPatronato)
    {


        // $this->creaUtente($cafPatronato);

        //Notifica ad agente
        dispatch(function () use ($cafPatronato) {
            $user = $cafPatronato->agente;
            try {
                $user->notify(new NotificaCafPatronatoAdAdmin($cafPatronato));
            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'ad agente per il servizio finanziario di ' . $cafPatronato->nominativo() . ': ' . $exception->getMessage(), 'error');
            }
        })->afterResponse();

        //Notifica vincenzo@studioschettino.com
        dispatch(function () use ($cafPatronato) {
            $user = new User();
            $user->email = 'vincenzo@studioschettino.com';
            try {
                $user->notify(new NotificaCafPatronato($cafPatronato));
            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il servizio finanziario di ' . $cafPatronato->nominativo() . ': ' . $exception->getMessage(), 'error');
            }
        })->afterResponse();

        //Notifica noreply@gestiio.it
        if ($this->currentUser()->hasPermissionTo('agente')) {
            dispatch(function () use ($cafPatronato) {
                $user = new User();
                $user->email = 'noreply@gestiio.it';
                try {
                    $user->notify(new NotificaCafPatronatoAdAdmin($cafPatronato));
                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il servizio finanziario di ' . $cafPatronato->nominativo() . ': ' . $exception->getMessage(), 'error');
                }
            })->afterResponse();
        }

    }

    /**
     * @param CafPatronato $cafPatronato
        * @return void
     */
    protected function creaUtente(CafPatronato $cafPatronato)
    {

        $user = User::where('email', $cafPatronato->email)->orWhere('telefono', $cafPatronato->cellulare)->first();
        if (!$user) {
            $user = new User();
            $user->nome = $cafPatronato->nome;
            $user->cognome = $cafPatronato->cognome;
            $user->email = $cafPatronato->email;
            $password = rand(11111111, 99999999);
            $user->password = Hash::make($password);
            $user->telefono = $cafPatronato->cellulare;
            $user->save();
        }


    }


}

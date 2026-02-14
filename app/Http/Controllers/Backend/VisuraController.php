<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\AllegatoCafPatronato;
use App\Models\AllegatoServizio;
use App\Models\AllegatoVisura;
use App\Models\CafPatronato;
use App\Models\EsitoVisura;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\TabMotivoKo;
use App\Models\TipoVisura;
use App\Models\User;
use App\Notifications\NotificaVisuraCambioEsitoAdAgente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Visura;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputToUpper;
use function App\importo;

class VisuraController extends Controller
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
        * @return mixed
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

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

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        $puoModificare = $this->puoModificare();
        $puoModificareEsito = $this->puoModificareEsito();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Visura.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render())
            ];

        }


        return view('Backend.Visura.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Visura::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\Visura::NOME_SINGOLARE,
            'testoCerca' => null,
            'puoModificare' => $puoModificare,
            'puoModificareEsito' => $puoModificareEsito,


        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Visura::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome)'), 'like', "%$t%");
            }
        }

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
        * @return mixed
     */
    public function create($servizio = null)
    {

        if (!$servizio) {
            return view('Backend.Visura.create', [
                'record' => new Visura(),
                'titoloPagina' => 'Nuova visura',
                'controller' => get_class($this),
            ]);
        }
        $record = new Visura();
        $record->data = today();
        $record->uid = Str::ulid();

        if ($this->currentUser()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        $tipoServizio = TipoVisura::find($servizio);

        return view('Backend.Visura.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova ' . $tipoServizio->nome,
            'controller' => get_class($this),
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco ' . Visura::NOME_PLURALE],
            'tipoServizio' => $tipoServizio
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
        $servizio = $request->input('tipo_visura_id');

        $request->validate($this->rules(null));

        DB::beginTransaction();
        $tipoServizio = TipoVisura::find($servizio);

        $record = new Visura();
        $record->esito_id = 'in-gestione';
        $record->prezzo_pratica = $tipoServizio->prezzo_agente;
        $record->tipo_visura_id = $tipoServizio->id;
        $record->caricato_da_user_id = Auth::id();
        if ($tipoServizio->tipo_visura == 'azienda') {
            $this->salvaDatiAzienda($record, $request);
        } else {
            $this->salvaDatiPrivato($record, $request);
        }
        $movimento = new MovimentoPortafoglio();
        $movimento->agente_id = Auth::id();
        $movimento->importo = -$tipoServizio->prezzo_agente;
        $movimento->descrizione = 'Visura ' . $tipoServizio->nome . ' per ' . $record->partita_iva;
        $movimento->prodotto_id = $record->id;
        $movimento->prodotto_type = get_class($record);
        $movimento->portafoglio = TipiPortafoglioEnum::SERVIZI->value;
        $movimento->save();

        DB::commit();

        $alertMessage = new AlertMessage();
        $alertMessage->messaggio('Ti è stato scalato l\'importo di ' . importo($tipoServizio->prezzo_agente) . ' per la visura ' . $tipoServizio->nome, 'primary')->titolo('Portafoglio aggiornato', 'primary')->flash();


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
        $record = Visura::find($id);
        abort_if(!$record, 404, 'Questa visura non esiste');
        return view('Backend.Visura.show', [
            'record' => $record,
            'controller' => VisuraController::class,
            'titoloPagina' => Visura::NOME_SINGOLARE,
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco ' . Visura::NOME_PLURALE]

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
        $record = Visura::find($id);
        abort_if(!$record, 404, 'Questa visura non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        return view('Backend.Visura.edit', [
            'record' => $record,
            'controller' => VisuraController::class,
            'titoloPagina' => 'Modifica ' . $record->tipo->nome,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco ' . Visura::NOME_PLURALE],
            'tipoServizio' => $record->tipo,


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
        $record = Visura::find($id);
        abort_if(!$record, 404, 'Questa ' . Visura::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        if ($record->tipo->tipo_visura == 'azienda') {
            $this->salvaDatiAzienda($record, $request);
        } else {
            $this->salvaDatiPrivato($record, $request);
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
        $record = Visura::find($id);
        abort_if(!$record, 404, 'Questa visura non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([VisuraController::class, 'index']),
        ];
    }


    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoVisura();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_visure.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->mime_type = $filePath->getMimeType();
            $contenuto = file_get_contents($filePath->getRealPath());
            $file->file_contenuto_base64 = $contenuto !== false ? base64_encode($contenuto) : null;
            $file->uid = $request->input('uid');
            $file->dimensione_file = $filePath->getSize();
            $file->visura_id = $request->input('visura_id');
            $file->per_cliente = $request->input('per_cliente', 0);
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoVisura::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }

    public function aggiornaStato(Request $request, $id)
    {
        $visura = Visura::withCount('allegati')->withCount('allegatiPerCliente')->find($id);
        abort_if(!$visura, 404, 'Questo servizio non esiste');

        $esitoPrima = $visura->esito_id;

        $esito = EsitoVisura::find($request->input('esito_id'));
        $motivoKoprima = $visura->motivo_ko;
        $visura->esito_id = $esito->id;
        $visura->esito_finale = $esito->esito_finale;
        $visura->pagato = getInputCheckbox($request->input('pagato'));
        $visura->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));

        $visura->save();

        if ($visura->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($visura->motivo_ko && strlen($visura->motivo_ko) < 70) {

                $tab = TabMotivoKo::firstOrNew(['nome' => $visura->motivo_ko, 'tipo' => 'caf-patronato']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$visura]);


        if ($visura->wasChanged(['esito_id'])) {
            $esito = EsitoVisura::find($visura->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($visura) {
                    /** @var User $agente */
                    $agente = $visura->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaVisuraCambioEsitoAdAgente($visura));
                    }

                })->afterResponse();

            }
            if ($this->currentUser()->hasPermissionTo('supervisore')) {
                Notifica::notificaAdAdmin('Cambio esito pratica', 'Esito per la pratica ' . $visura->nominativo() . ' modificato a ' . $esito->nome);
            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.servizi';
        } else {
            $view = 'Backend.Visura.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => VisuraController::class,
                'puoModificare' => $this->puoModificare(),
                'puoModificareEsito' => $this->puoModificareEsito(),

            ])->render())
        ];
    }


    /**
     * @param Visura $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiAzienda($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'partita_iva' => '',
            'ragione_sociale' => 'app\getInputUcwords',
            'citta' => '',
            'note' => '',
            'uid' => '',
            'pagato' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();
        return $model;
    }

    /**
     * @param Visura $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiPrivato($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'note' => '',
            'uid' => '',
            'codice_fiscale' => 'strtoupper',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'cellulare' => '',
            'indirizzo' => 'app\getInputUcwords',
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
        return \App\Models\Visura::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'partita_iva' => ['nullable', new \App\Rules\PartitaIvaRule()],
            'ragione_sociale' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'note' => ['nullable'],
            'uid' => ['required', 'max:255'],
            'esito_finale' => ['nullable', 'max:255'],
            'mese_pagamento' => ['nullable'],
            'motivo_ko' => ['nullable', 'max:255'],
            'pagato' => ['nullable'],
        ];

        return $rules;
    }

    protected function puoModificareEsito()
    {
        return $this->currentUser()->hasAnyPermission(['admin', 'supervisore']);
    }

    protected function puoModificare()
    {
        return !$this->currentUser()->hasPermissionTo('supervisore');
    }


}

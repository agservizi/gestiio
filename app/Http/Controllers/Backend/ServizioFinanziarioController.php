<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Funzioni\Twilio;
use App\Models\AllegatoContratto;
use App\Models\AllegatoServizio;
use App\Models\Cliente;
use App\Models\EsitoServizioFinanziario;
use App\Models\Notifica;
use App\Models\ServizioMutuo;
use App\Models\ServizioPolizza;
use App\Models\ServizioPolizzaFacile;
use App\Models\ServizioPrestito;
use App\Models\TabMotivoKo;
use App\Models\User;
use App\Notifications\NotificaAdminServizioFinanziario;
use App\Notifications\NotificaAgenteCambioEsitoServizioFinanziario;
use App\Notifications\NotificaAgenteNuovoServizioFinanziario;
use App\Notifications\NotificaClienteServizioFinanziario;
use App\Notifications\NotificaDatiAccessoClienteServizioFinanziario;
use App\Notifications\NotificaEuroAnsa;
use App\Notifications\NotificaPolizzaFacile;
use App\Rules\CodiceFiscaleRule;
use App\Rules\DataItalianaRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServizioFinanziario;
use DB;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputNumero;
use function App\getInputToUpper;

class ServizioFinanziarioController extends Controller
{
    protected $conFiltro = false;


    /**
     * Display a listing of the resource.
     *
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
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

        $orderByUser = Auth::user()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } else if ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }

        //Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();
        $puoModificare = Auth::user()->hasPermissionTo('admin');

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.ServizioFinanziario.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare

                ]))
            ];

        }


        return view('Backend.ServizioFinanziario.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ServizioFinanziario::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova segnalazione ',
            'testoCerca' => 'Cerca in nominativo, email, telefono',
            'puoModificare' => $puoModificare
        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ServizioFinanziario::query()
            ->with('esito')
            ->with('agente')
            ->withCount('allegati');

        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,email,cellulare)'), 'like', "%$t%");
            }
        }

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($servizio = null)
    {
        if (!$servizio) {
            return view('Backend.ServizioFinanziario.create', [
                'record' => new ServizioFinanziario(),
                'titoloPagina' => 'Nuova segnalazione',
                'controller' => get_class($this),
                'breadcrumbs' => [action([ServizioFinanziarioController::class, 'index']) => 'Torna a elenco ' . ServizioFinanziario::NOME_PLURALE]
            ]);
        }

        $record = new ServizioFinanziario();
        $record->data = now();
        $record->uid = Str::ulid();

        $classeProdotto = 'App\Models\\' . $servizio;
        if (Auth::user()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        return view('Backend.ServizioFinanziario.edit', [
            'record' => $record,
            'recordProdotto' => new $classeProdotto(),
            'titoloPagina' => 'Nuova segnalazione ' . str_replace('Servizio', '', $servizio),
            'controller' => get_class($this),
            'tipoServizio' => $servizio,
            'breadcrumbs' => [action([ServizioFinanziarioController::class, 'index']) => 'Torna a elenco ' . ServizioFinanziario::NOME_PLURALE],
            'allegatoServizioType' => ServizioFinanziario::class,

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $servizio = $request->input('tipo_servizio');

        $funcRules = 'rules' . $servizio;
        $rules = array_merge($this->rules(), $this->$funcRules());


        $request->validate($rules);


        $record = new ServizioFinanziario();
        $record->esito_id = 'da-gestire';
        DB::beginTransaction();
        $this->salvaDati($record, $request);

        $func = 'salvaDati' . $servizio;


        $this->$func($record, $request);
        DB::commit();

        $this->inviaNotifiche($record);
        if (Auth::user()->hasPermissionTo('agente')) {
            Notifica::notificaAdAdmin('Nuovo servizio finanziario', '<span class="fw-bold">' . $record->tipoProdottoBlade() . '</span> caricato da <span class="fw-bold">' . $record->agente->nominativo() . '</span> per il cliente <span class="fw-bold">' . $record->nominativo() . '</span>');
        }

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = ServizioFinanziario::find($id);
        abort_if(!$record, 404, 'Questo servizio finanziario non esiste');
        return view('Backend.ServizioFinanziario.show', [
            'record' => $record,
            'controller' => ServizioFinanziarioController::class,
            'titoloPagina' => ServizioFinanziario::NOME_SINGOLARE,
            'breadcrumbs' => [action([ServizioFinanziarioController::class, 'index']) => 'Torna a elenco ' . ServizioFinanziario::NOME_PLURALE]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = ServizioFinanziario::find($id);
        abort_if(!$record, 404, 'Questo servizio finanziario non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.ServizioFinanziario.edit', [
            'record' => $record,
            'recordProdotto' => $record->prodotto,
            'tipoServizio' => $record->tipoProdotto(),
            'controller' => ServizioFinanziarioController::class,
            'titoloPagina' => 'Modifica segnalazione ' . $record->tipoProdottoBlade(),
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ServizioFinanziarioController::class, 'index']) => 'Torna a elenco ' . ServizioFinanziario::NOME_PLURALE],
            'allegatoServizioType' => ServizioFinanziario::class,


        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $record = ServizioFinanziario::find($id);
        abort_if(!$record, 404, 'Questo ' . ServizioFinanziario::NOME_SINGOLARE . ' non esiste');
        $servizio = $record->tipoProdotto();
        $funcRules = 'rules' . $servizio;
        $rules = array_merge($this->rules($id), $this->$funcRules($id));
        $request->validate($rules);
        $this->salvaDati($record, $request);
        $func = 'salvaDati' . $record->tipoProdotto();

        $this->$func($record, $request, $record);

        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $record = ServizioFinanziario::find($id);
        abort_if(!$record, 404, 'Questo servizio finanziario non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ServizioFinanziarioController::class, 'index']),
        ];
    }

    public function aggiornaStato(Request $request, $id)
    {
        $servizioFinanziario = ServizioFinanziario::find($id);
        abort_if(!$servizioFinanziario, 404, 'Questo servizio non esiste');

        $esitoPrima = $servizioFinanziario->esito_id;

        $esito = EsitoServizioFinanziario::find($request->input('esito_id'));
        $motivoKoprima = $servizioFinanziario->motivo_ko;
        $servizioFinanziario->esito_id = $esito->id;
        $servizioFinanziario->esito_finale = $esito->esito_finale;
        $servizioFinanziario->pagato = getInputCheckbox($request->input('pagato'));
        $servizioFinanziario->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));
        if ($esito->esito_finale == 'ko') {
            $servizioFinanziario->provvigione_agente = 0;
            $servizioFinanziario->provvigione_agenzia = 0;
        } else {
            $servizioFinanziario->provvigione_agente = getInputNumero($request->input('provvigione_agente')) ?? 0;
            $servizioFinanziario->provvigione_agenzia = getInputNumero($request->input('provvigione_agenzia')) ?? 0;
        }


        $servizioFinanziario->save();

        if ($servizioFinanziario->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($servizioFinanziario->motivo_ko && strlen($servizioFinanziario->motivo_ko) < 70) {

                $tab = TabMotivoKo::firstOrNew(['nome' => $servizioFinanziario->motivo_ko, 'tipo' => 'servizio-finanziario']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$servizioFinanziario]);


        if ($servizioFinanziario->wasChanged(['esito_id'])) {
            $esito = EsitoServizioFinanziario::find($servizioFinanziario->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($servizioFinanziario) {
                    $agente = $servizioFinanziario->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaAgenteCambioEsitoServizioFinanziario($servizioFinanziario));
                    }
                })->afterResponse();

            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.servizi';
        } else {
            $view = 'Backend.ServizioFinanziario.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => ContrattoTelefoniaController::class,
                'puoModificare' => Auth::user()->hasPermissionTo('admin')
            ]))
        ];
    }


    /**
     * @param ServizioFinanziario $servizioFinanziario
     * @return void
     */
    public function inviaNotifiche($servizioFinanziario)
    {


        $this->creaUtente($servizioFinanziario);

        //Notifica ad agente
        dispatch(function () use ($servizioFinanziario) {
            $user = $servizioFinanziario->agente;
            try {
                $user->notify(new NotificaAgenteNuovoServizioFinanziario($servizioFinanziario));

            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'ad agente per il servizio finanziario di ' . $servizioFinanziario->nominativo() . ': ' . $exception->getMessage(), 'error');
            }

        })->afterResponse();


        //Notifica sky
        dispatch(function () use ($servizioFinanziario) {

            if ($servizioFinanziario->tipoProdotto() == 'ServizioPolizzaFacile') {
                $user = new User();
                $user->email = 'micciosalvatore@hotmail.it';
                try {
                    $user->notify(new NotificaPolizzaFacile($servizioFinanziario));
                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il servizio finanziario di ' . $servizioFinanziario->nominativo() . ': ' . $exception->getMessage(), 'error');
                }

            } else {
                $user = new User();
                $user->email = 'info@dovosrls.it';
                try {
                    $user->notify(new NotificaEuroAnsa($servizioFinanziario));
                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il servizio finanziario di ' . $servizioFinanziario->nominativo() . ': ' . $exception->getMessage(), 'error');
                }

            }

        })->afterResponse();

        //Notifica noreply@gestiio.it
        if (Auth::user()->hasPermissionTo('agente')) {
            dispatch(function () use ($servizioFinanziario) {
                $user = new User();
                $user->email = 'noreply@gestiio.it';
                try {
                    $user->notify(new NotificaAdminServizioFinanziario($servizioFinanziario));

                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per il servizio finanziario di ' . $servizioFinanziario->nominativo() . ': ' . $exception->getMessage(), 'error');

                }
            })->afterResponse();

        }

    }

    /**
     * @param ServizioFinanziario $servizioFinanziario
     * @return int
     */
    protected function creaUtente(ServizioFinanziario $servizioFinanziario)
    {

        $user = User::where('email', $servizioFinanziario->email)->orWhere('telefono', $servizioFinanziario->cellulare)->first();
        if (!$user) {
            $user = new User();
            $user->nome = $servizioFinanziario->nome;
            $user->cognome = $servizioFinanziario->cognome;
            $user->email = $servizioFinanziario->email;
            $password = rand(11111111, 99999999);
            $user->password = \Hash::make($password);
            $user->telefono = $servizioFinanziario->cellulare;
            $user->save();

            dispatch(function () use ($servizioFinanziario, $password, $user) {
                //Notifica a cliente
                try {
                    $user->notify(new NotificaDatiAccessoClienteServizioFinanziario($servizioFinanziario, $password));
                    $user->invio_dati_accesso = now();
                    $user->save();
                } catch (\Exception $exception) {
                    report($exception);
                    Notifica::notificaAdAdmin('Errore nell\'invio dati accesso cliente', 'a ' . $user->nominativo() . ': ' . $exception->getMessage(), 'error');

                }
            })->afterResponse();

        } else {
            dispatch(function () use ($servizioFinanziario, $user) {
                //Notifica a cliente
                $user->notify(new NotificaClienteServizioFinanziario($servizioFinanziario));
            })->afterResponse();

        }


    }


    /**
     * @param ServizioFinanziario $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

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

        $model->cliente_id = $this->salvaDatiCliente($cliente, $model);


        $model->save();


        AllegatoServizio::where('uid', $model->uid)->update(['allegato_id' => $model->id, 'uid' => null]);


        return $model;
    }


    /**
     * @param ServizioPrestito $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiServizioPrestito($ordineModel, $request)
    {
        $model = $ordineModel->prodotto;
        $nuovo = false;

        if (!$model) {
            $nuovo = true;
            $model = new ServizioPrestito();
            $model->servizio_id = $ordineModel->id;
        }


        //Ciclo su campi
        $campi = [
            'importo_prestito' => 'app\getInputNumero',
            'durata_prestito' => '',
            'stato_civile' => '',
            'immobile_residenza' => '',
            'telefono_fisso' => '',
            'prestiti_in_corso' => 'app\getInputCheckbox',
            'prestiti_in_passato' => 'app\getInputCheckbox',
            'motivazione_prestito' => '',
            'componenti_famiglia' => '',
            'componenti_famiglia_con_reddito' => '',
            'lavoro' => '',
            'datore_lavoro_intestazione' => '',
            'mesi_anzianita_servizio' => '',
            'anni_anzianita_servizio' => '',
            'indirizzo_lavoro' => '',
            'citta_lavoro' => '',
            'telefono_lavoro' => '',
            'titolo_studio' => '',
            'reddito_mensile' => 'app\getInputNumero',
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
            $ordineModel->prodotto_id = $model->servizio_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

        return $model;
    }


    /**
     * @param ServizioPolizza $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiServizioPolizza($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;

        if (!$model) {
            $nuovo = true;
            $model = new ServizioPolizza();
            $model->servizio_id = $ordineModel->id;

        }

        //Ciclo su campi
        $campi = [
            'targa' => '',
            'data_di_nascita' => 'app\getInputData',
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
            $ordineModel->prodotto_id = $model->servizio_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

        return $model;
    }


    /**
     * @param ServizioPolizzaFacile $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiServizioPolizzaFacile($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;

        if (!$model) {
            $nuovo = true;
            $model = new ServizioPolizzaFacile();
            $model->servizio_id = $ordineModel->id;

        }

        //Ciclo su campi
        $campi = [
            'targa' => '',
            'data_di_nascita' => 'app\getInputData',
            'importo_attuale' => 'app\getInputNumero',
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
            $ordineModel->prodotto_id = $model->servizio_id;
            $ordineModel->prodotto_type = get_class($model);
            $ordineModel->save();
        }

        return $model;
    }


    /**
     * @param Cliente $model
     * @param ServizioFinanziario $request
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
        ];
        foreach ($campi as $campo => $funzione) {
            $model->$campo = $request->$campo;
        }

        $model->telefono = $request->cellulare;
        $model->save();


        return $model->id;
    }

    /**
     * @param ServizioMutuo $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiServizioMutuo($ordineModel, $request)
    {

        $model = $ordineModel->prodotto;
        $nuovo = false;

        if (!$model) {
            $nuovo = true;
            $model = new ServizioMutuo();
            $model->servizio_id = $ordineModel->id;

        }

        //Ciclo su campi
        $campi = [
            'finalita' => '',
            'tipo_di_tasso' => '',
            'valore_immobile' => 'app\getInputNumero',
            'importo_del_mutuo' => 'app\getInputNumero',
            'durata' => '',
            'data_di_nascita' => 'app\getInputData',
            'posizione_lavorativa' => '',
            'reddito_richiedenti' => 'app\getInputNumero',
            'comune_domicilio' => '',
            'comune_immobile' => '',
            'stato_ricerca_immobile' => '',
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
            $ordineModel->prodotto_id = $model->servizio_id;
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
        return \App\Models\ServizioFinanziario::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['required', 'max:255'],
            'cellulare' => ['required', 'max:255'],
            'prodotto_id' => ['nullable'],
            'prodotto_type' => ['nullable', 'max:255'],
            'codice_fiscale' => ['required', new CodiceFiscaleRule()],
        ];

        return $rules;
    }

    protected function rulesServizioPrestito($id = null)
    {


        $rules = [
            'importo_prestito' => ['required'],
            'durata_prestito' => ['required'],
            'stato_civile' => ['required', 'max:255'],
            'immobile_residenza' => ['required', 'max:255'],
            'telefono_fisso' => ['required', 'max:255'],
            'motivazione_prestito' => ['required', 'max:255'],
            'componenti_famiglia' => ['required', 'numeric'],
            'componenti_famiglia_con_reddito' => ['required', 'numeric'],
            'lavoro' => ['required', 'max:255'],
            'datore_lavoro_intestazione' => ['nullable', 'max:255'],
            'mesi_anzianita_servizio' => ['nullable'],
            'anni_anzianita_servizio' => ['nullable'],
            'indirizzo_lavoro' => ['nullable', 'max:255'],
            'citta_lavoro' => ['nullable', 'max:255'],
            'telefono_lavoro' => ['nullable', 'max:255'],
            'titolo_studio' => ['required', 'max:255'],
            'reddito_mensile' => ['required'],
        ];

        return $rules;
    }

    protected function rulesServizioPolizza($id = null)
    {


        $rules = [
            'targa' => ['required', 'max:255'],
            'data_di_nascita' => ['required', new DataItalianaRule()],
        ];

        return $rules;
    }

    protected function rulesServizioMutuo($id = null)
    {


        $rules = [
            'finalita' => ['required', 'max:255'],
            'tipo_di_tasso' => ['required', 'max:255'],
            'valore_immobile' => ['required'],
            'importo_del_mutuo' => ['required'],
            'durata' => ['required'],
            'data_di_nascita' => ['required'],
            'posizione_lavorativa' => ['required', 'max:255'],
            'reddito_richiedenti' => ['required'],
            'comune_domicilio' => ['required', 'max:255'],
            'comune_immobile' => ['required', 'max:255'],
            'stato_ricerca_immobile' => ['required', 'max:255'],
        ];

        return $rules;
    }

    protected function rulesServizioPolizzaFacile($id = null)
    {


        $rules = [
            'targa' => ['required', 'max:255'],
            'data_di_nascita' => ['required', new DataItalianaRule()],
        ];

        return $rules;
    }


}

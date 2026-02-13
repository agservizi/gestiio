<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoAttivazioneSim;
use App\Models\CafPatronato;
use App\Models\EsitoAttivazioneSim;
use App\Models\EsitoCafPatronato;
use App\Models\Gestore;
use App\Models\GestoreAttivazioniSim;
use App\Models\Notifica;
use App\Models\SostituzioneSim;
use App\Models\TabMotivoKo;
use App\Models\User;
use App\Notifications\NotificaAttivazioneSimAdAdmin;
use App\Notifications\NotificaAttivazioneSimCambioEsitoAdAgente;
use App\Notifications\NotificaCafPatronato;
use App\Notifications\NotificaCafPatronatoAdAdmin;
use App\Notifications\NotificaCafPatronatoCambioEsitoAdAgente;
use App\Notifications\NotificaGenericaGestore;
use App\Notifications\NotificaGenericaGestoreAttivazioneSim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttivazioneSim;
use DB;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputToUpper;

class AttivazioneSimController extends Controller
{
    protected $conFiltro = false;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

        $puoModificare = $this->determinaPuoModificare();
        $puoCambiareStato = $this->determinaPuoCambiareStato();


        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.AttivazioneSim.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare,
                    'puoCambiareStato' => $puoCambiareStato,

                ]))
            ];

        }


        return view('Backend.AttivazioneSim.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\AttivazioneSim::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\AttivazioneSim::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nominativo, codice fiscale',
            'puoModificare' => $puoModificare,
            'puoCambiareStato' => $puoCambiareStato,


        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\AttivazioneSim::query()
            ->with(['comune' => function ($q) {
                $q->select('id', 'comune', 'targa');
            }])
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
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,codice_fiscale)'), 'like', "%$t%");
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
            return view('Backend.AttivazioneSim.create', [
                'record' => new AttivazioneSim(),
                'titoloPagina' => 'Nuova attivazione',
                'controller' => get_class($this),
                'breadcrumbs' => [action([AttivazioneSimController::class, 'index']) => 'Torna a elenco ' . AttivazioneSim::NOME_PLURALE]
            ]);
        }

        $record = new AttivazioneSim();
        $record->data = today();
        $record->uid = Str::ulid();
        $record->gestore_id = $servizio;
        if (Auth::user()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        return view('Backend.AttivazioneSim.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova ' . AttivazioneSim::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([AttivazioneSimController::class, 'index']) => 'Torna a elenco ' . AttivazioneSim::NOME_PLURALE],
            'nomeGestore' => GestoreAttivazioniSim::find($record->gestore_id)->nome
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
        $request->validate($this->rules(null));
        $record = new AttivazioneSim();
        $this->salvaDati($record, $request);


        $this->inviaNotifiche($record);

        if (Auth::user()->hasPermissionTo('agente')) {
            $gestore = GestoreAttivazioniSim::find($record->gestore_id);
            Notifica::notificaAdAdmin('Nuova ' . AttivazioneSim::NOME_SINGOLARE, '<span class="fw-bold">' . $gestore->nome . '</span> caricato da <span class="fw-bold">' . $record->agente->nominativo() . '</span> per il cliente <span class="fw-bold">' . $record->nominativo() . '</span>');
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
        $record = AttivazioneSim::find($id);
        abort_if(!$record, 404, 'Questa attivazione sim non esiste');
        return view('Backend.AttivazioneSim.show', [
            'record' => $record,
            'controller' => AttivazioneSimController::class,
            'titoloPagina' => ucfirst(AttivazioneSim::NOME_SINGOLARE),
            'breadcrumbs' => [action([AttivazioneSimController::class, 'index']) => 'Torna a elenco ' . AttivazioneSim::NOME_PLURALE],
            'sostituzioni' => SostituzioneSim::where('attivazione_sim_id', $id)->with('agente:id,ragione_sociale')->get(),
            'nomeGestore' => GestoreAttivazioniSim::find($record->gestore_id)->nome

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
        $record = AttivazioneSim::find($id);
        abort_if(!$record, 404, 'Questa attivazionesim non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.AttivazioneSim.edit', [
            'record' => $record,
            'controller' => AttivazioneSimController::class,
            'titoloPagina' => 'Modifica ' . AttivazioneSim::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([AttivazioneSimController::class, 'index']) => 'Torna a elenco ' . AttivazioneSim::NOME_PLURALE],
            'nomeGestore' => GestoreAttivazioniSim::find($record->gestore_id)->nome,
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
        $record = AttivazioneSim::find($id);
        abort_if(!$record, 404, 'Questa ' . AttivazioneSim::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
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
        $record = AttivazioneSim::find($id);
        abort_if(!$record, 404, 'Questa attivazionesim non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([AttivazioneSimController::class, 'index']),
        ];
    }


    public function aggiornaStato(Request $request, $id)
    {
        $attivazioneSims = AttivazioneSim::withCount('allegati')->find($id);
        abort_if(!$attivazioneSims, 404, 'Questo servizio non esiste');

        $esitoPrima = $attivazioneSims->esito_id;

        $esito = EsitoAttivazioneSim::find($request->input('esito_id'));
        $motivoKoprima = $attivazioneSims->motivo_ko;
        $attivazioneSims->esito_id = $esito->id;
        $attivazioneSims->esito_finale = $esito->esito_finale;
        $attivazioneSims->pagato = getInputCheckbox($request->input('pagato'));
        $attivazioneSims->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));

        $attivazioneSims->save();

        if ($attivazioneSims->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($attivazioneSims->motivo_ko && strlen($attivazioneSims->motivo_ko) < 70) {

                $tab = TabMotivoKo::firstOrNew(['nome' => $attivazioneSims->motivo_ko, 'tipo' => 'attivazione']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$attivazioneSims]);


        if ($attivazioneSims->wasChanged(['esito_id'])) {
            $esito = EsitoAttivazioneSim::find($attivazioneSims->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($attivazioneSims) {
                    $agente = $attivazioneSims->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaAttivazioneSimCambioEsitoAdAgente($attivazioneSims));
                    }

                })->afterResponse();

            }
            if (Auth::user()->hasPermissionTo('supervisore')) {
                Notifica::notificaAdAdmin('Cambio esito attivazione', 'Esito per l\'attivazione sim ' . $attivazioneSims->nominativo() . ' modificato a ' . $esito->nome);
            }

        }


        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.servizi';
        } else {
            $view = 'Backend.AttivazioneSim.tbody';
        }


        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => AttivazioneSimController::class,
                'puoModificare' => $this->determinaPuoModificare(),
                'puoCambiareStato' => $this->determinaPuoCambiareStato(),

            ]))
        ];
    }


    public function downloadAllegato($attivazioneId, $allegatoId)
    {
        $record = AllegatoAttivazioneSim::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->attivazioni_sim_id != $attivazioneId, 404, 'Questo allegato non esiste');
        return response()->download(\Storage::path($record->path_filename), $record->filename_originale);
    }


    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoAttivazioneSim();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_attivazioni_sim.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->uid = $request->input('uid');
            $file->dimensione_file = $filePath->getSize();
            if ($request->input('attivazioni_sim_id') > 0) {
                $file->attivazioni_sim_id = $request->input('attivazioni_sim_id');
            }
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName, 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoAttivazioneSim::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        \Log::debug(__FUNCTION__, $record->toArray());

        \Log::debug('elimino AllegatoAttivazioneSim' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }


    /**
     * @param AttivazioneSim $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->esito_id = 'in-gestione';
            $model->caricato_da_user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'gestore_id' => '',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'cellulare' => 'app\getInputTelefono',
            'codice_fiscale' => 'strtoupper',
            'cliente_id' => '',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'note' => '',
            'uid' => '',
            'codice_sim' => '',
            'tipo_documento' => '',
            'mnp' => '',
            'numero_documento' => '',
            'numero_da_portare' => '',
            'offerta_sim_id' => '',
            'puk' => '',
            'data_scadenza' => 'app\getInputData',
            'convergenza_mobile' => 'app\getInputCheckbox',
            'easy_pay' => 'app\getInputCheckbox',
            'giga_illimitati_superfibra' => 'app\getInputCheckbox',
            'smartphone_a_rate' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();


        AllegatoAttivazioneSim::where('uid', $model->uid)->whereNull('attivazioni_sim_id')->update(['attivazioni_sim_id' => $model->id, 'uid' => null]);

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
        return \App\Models\AttivazioneSim::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'gestore_id' => ['required'],
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['required', 'max:255'],
            'cellulare' => ['required', 'max:255'],
            'codice_fiscale' => ['required', new \App\Rules\CodiceFiscaleRule()],
            'cliente_id' => ['nullable'],
            'indirizzo' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'cap' => ['nullable'],
            'note' => ['nullable'],
            'uid' => ['required', 'max:255'],
            'codice_sim' => ['required', 'max:255'],
            'tipo_documento' => ['required', 'max:255'],
            'numero_documento' => ['required', 'max:255'],
            'data_scadenza' => ['required'],
            'mnp' => ['required_if:portabilita,1']
        ];

        return $rules;
    }

    protected function determinaPuoModificare()
    {
        return Auth::user()->hasAnyPermission(['admin', 'supervisore']);

    }

    protected function determinaPuoCambiareStato()
    {
        return $this->determinaPuoModificare() || Auth::user()->hasPermissionTo('supervisore');

    }

    /**
     * @param AttivazioneSim $attivazioneSim
     * @return void
     */
    public function inviaNotifiche($attivazioneSim)
    {


        // $this->creaUtente($cafPatronato);

        //Notifica ad agente
        dispatch(function () use ($attivazioneSim) {
            $user = $attivazioneSim->agente;
            try {
                $user->notify(new NotificaAttivazioneSimAdAdmin($attivazioneSim));
            } catch (\Exception $exception) {
                report($exception);
                Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'ad agente per il servizio finanziario di ' . $attivazioneSim->nominativo() . ': ' . $exception->getMessage(), 'error');
            }
        })->afterResponse();


        //Notifiche al gestore

        if ($attivazioneSim->gestore->email_notifica_a_gestore) {

            dispatch(function () use ($attivazioneSim) {
                foreach (explode(';', $attivazioneSim->gestore->email_notifica_a_gestore) as $email) {
                    $user = new User();
                    $user->email = trim($email);
                    try {
                        $user->notify(new NotificaGenericaGestoreAttivazioneSim($attivazioneSim));
                    } catch (\Exception $exception) {
                        report($exception);
                        Notifica::notificaAdAdmin('Errore nell\'invio della notifica', 'a ' . $user->email . ' per attivazione sim di ' . $attivazioneSim->nominativo() . ': ' . $exception->getMessage(), 'error');
                    }
                }
            })->afterResponse();

        }


    }


}

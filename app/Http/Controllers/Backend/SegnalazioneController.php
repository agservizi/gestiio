<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContrattoTelefonia;
use App\Models\EsitoSegnalazione;
use App\Models\EsitoTelefonia;
use App\Models\User;
use App\Notifications\NotificaAdminSegnalazione;
use App\Notifications\NotificaAgenteCambioEsitoContratto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Segnalazione;
use DB;
use function App\getInputCheckbox;

class SegnalazioneController extends Controller
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

            'nominativo' => ['testo' => 'Nome azienda', 'filtro' => function ($q) {
                return $q->orderBy('nome_azienda');
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


        $puoCambiareStato = Segnalazione::determinaPuoCambiareStato();
        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Segnalazione.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoCambiareStato' => $puoCambiareStato
                ]))
            ];

        }


        return view('Backend.Segnalazione.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Segnalazione::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\Segnalazione::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nome azienda',
            'puoCambiareStato' => $puoCambiareStato


        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Segnalazione::query()
            ->with('esito:id,nome,colore_hex')
            ->with('agente');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome_azienda)'), 'like', "%$t%");
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
    public function create()
    {
        $record = new Segnalazione();
        if (Auth::user()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }
        return view('Backend.Segnalazione.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Segnalazione::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([SegnalazioneController::class, 'index']) => 'Torna a elenco ' . Segnalazione::NOME_PLURALE]

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
        $record = new Segnalazione();
        $record->esito_id = 'in-gestione';
        $this->salvaDati($record, $request);

        if (Auth::user()->hasPermissionTo('agente')) {
            dispatch(function () use ($record) {
                User::find(2)->notify(new NotificaAdminSegnalazione($record));
            })->afterResponse();
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
        $record = Segnalazione::find($id);
        abort_if(!$record, 404, 'Questa segnalazione non esiste');
        return view('Backend.Segnalazione.show', [
            'record' => $record,
            'controller' => SegnalazioneController::class,
            'titoloPagina' => Segnalazione::NOME_SINGOLARE,
            'breadcrumbs' => [action([SegnalazioneController::class, 'index']) => 'Torna a elenco ' . Segnalazione::NOME_PLURALE]

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
        $record = Segnalazione::find($id);
        abort_if(!$record, 404, 'Questa segnalazione non esiste');

        $eliminabile = Auth::user()->hasPermissionTo('admin');

        return view('Backend.Segnalazione.edit', [
            'record' => $record,
            'controller' => SegnalazioneController::class,
            'titoloPagina' => 'Modifica ' . Segnalazione::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([SegnalazioneController::class, 'index']) => 'Torna a elenco ' . Segnalazione::NOME_PLURALE]

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
        $record = Segnalazione::find($id);
        abort_if(!$record, 404, 'Questa ' . Segnalazione::NOME_SINGOLARE . ' non esiste');
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
        $record = Segnalazione::find($id);
        abort_if(!$record, 404, 'Questa segnalazione non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([SegnalazioneController::class, 'index']),
        ];
    }

    public function aggiornaStato(Request $request, $id)
    {
        $contratto = Segnalazione::find($id);
        abort_if(!$contratto, 404, 'Questa segnalazione non esiste');

        $esitoPrima = $contratto->esito_id;

        $esito = EsitoSegnalazione::find($request->input('esito_id'));
        $contratto->esito_id = $esito->id;
        $contratto->save();


        $records = collect([$contratto]);


        if ($esitoPrima == 'bozza') {
            $this->inviaNotifiche($contratto);
        }


        if ($contratto->wasChanged(['esito_id'])) {
            $esito = EsitoSegnalazione::find($contratto->esito_id);
        }


        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view('Backend.Segnalazione.tbody', [
                'records' => $records,
                'controller' => ContrattoTelefoniaController::class,
                'puoModificare' => ContrattoTelefonia::determinaPuoModificare(),
                'puoCreare' => ContrattoTelefonia::determinaPuoCreare(),
                'puoCambiareStato' => ContrattoTelefonia::determinaPuoCambiareStato(),
            ]))
        ];
    }


    /**
     * @param Segnalazione $model
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
            'agente_id' => '',
            'nome_azienda' => '',
            'partita_iva' => '',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'telefono' => '',
            'nome_referente' => '',
            'cognome_referente' => '',
            'email_referente' => '',
            'fatturato' => '',
            'settore' => '',
            'provincia' => '',
            'forma_giuridica' => '',
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
        return \App\Models\Segnalazione::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'agente_id' => ['required'],
            'nome_azienda' => ['required', 'max:255'],
            'partita_iva' => ['required', new \App\Rules\PartitaIvaRule()],
            'indirizzo' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'cap' => ['nullable'],
            'telefono' => ['required', new \App\Rules\TelefonoRule()],
            'nome_referente' => ['required', 'max:255'],
            'cognome_referente' => ['required', 'max:255'],
            'email_referente' => ['required', 'max:255'],
            'fatturato' => ['nullable', 'max:255'],
            'settore' => ['nullable', 'max:255'],
            'provincia' => ['nullable', 'max:255'],
            'forma_giuridica' => ['nullable', 'max:255'],
        ];

        return $rules;
    }

}

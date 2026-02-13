<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\EsitoComparasemplice;
use App\Models\EsitoServizioFinanziario;
use App\Models\ServizioFinanziario;
use App\Models\TabMotivoKo;
use App\Notifications\NotificaAgenteCambioEsitoComparasemplice;
use App\Notifications\NotificaAgenteCambioEsitoServizioFinanziario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comparasemplice;
use DB;
use Illuminate\Support\Str;
use function App\getInputCheckbox;
use function App\getInputNumero;
use function App\getInputToUpper;

class ComparasempliceController extends Controller
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

        $puoModificare = Auth::user()->hasPermissionTo('admin');

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Comparasemplice.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare

                ]))
            ];

        }

        return view('Backend.Comparasemplice.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Comparasemplice::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\Comparasemplice::NOME_SINGOLARE,
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

        $queryBuilder = \App\Models\Comparasemplice::query()
            ->with('esito')
            ->with('agente')
            ->withCount('allegati');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,email,telefono)'), 'like', "%$t%");
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

        $record = new Comparasemplice();
        if (Auth::user()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }
        $record->data = today();
        $record->uid = Str::ulid();

        return view('Backend.Comparasemplice.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Comparasemplice::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ComparasempliceController::class, 'index']) => 'Torna a elenco ' . Comparasemplice::NOME_PLURALE],
            'allegatoServizioType' => Comparasemplice::class,


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
        $record = new Comparasemplice();
        $record->esito_id = 'in-gestione';
        $this->salvaDati($record, $request);
        return redirect()->action([ComparasempliceController::class, 'show'], $record->id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = Comparasemplice::find($id);
        abort_if(!$record, 404, 'Questo comparasemplice non esiste');
        return view('Backend.Comparasemplice.show', [
            'record' => $record,
            'controller' => ComparasempliceController::class,
            'titoloPagina' => Comparasemplice::NOME_SINGOLARE,
            'breadcrumbs' => [action([ComparasempliceController::class, 'index']) => 'Torna a elenco ' . Comparasemplice::NOME_PLURALE]

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
        $record = Comparasemplice::find($id);
        abort_if(!$record, 404, 'Questo comparasemplice non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        return view('Backend.Comparasemplice.edit', [
            'record' => $record,
            'controller' => ComparasempliceController::class,
            'titoloPagina' => 'Modifica ' . Comparasemplice::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ComparasempliceController::class, 'index']) => 'Torna a elenco ' . Comparasemplice::NOME_PLURALE],
            'allegatoServizioType' => Comparasemplice::class,
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
        $record = Comparasemplice::find($id);
        abort_if(!$record, 404, 'Questo ' . Comparasemplice::NOME_SINGOLARE . ' non esiste');
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
        $record = Comparasemplice::find($id);
        abort_if(!$record, 404, 'Questo comparasemplice non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ComparasempliceController::class, 'index']),
        ];
    }


    public function aggiornaStato(Request $request, $id)
    {
        $servizioFinanziario = Comparasemplice::find($id);
        abort_if(!$servizioFinanziario, 404, 'Questo servizio non esiste');

        $esitoPrima = $servizioFinanziario->esito_id;

        $esito = EsitoComparasemplice::find($request->input('esito_id'));
        $motivoKoprima = $servizioFinanziario->motivo_ko;
        $servizioFinanziario->esito_id = $esito->id;
        $servizioFinanziario->esito_finale = $esito->esito_finale;
        $servizioFinanziario->pagato = getInputCheckbox($request->input('pagato'));
        $servizioFinanziario->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));
        if ($esito->esito_finale == 'ko') {
            //$servizioFinanziario->provvigione_agente = 0;
            //$servizioFinanziario->provvigione_agenzia = 0;
        } else {
            //$servizioFinanziario->provvigione_agente = getInputNumero($request->input('provvigione_agente')) ?? 0;
            //$servizioFinanziario->provvigione_agenzia = getInputNumero($request->input('provvigione_agenzia')) ?? 0;
        }


        $servizioFinanziario->save();

        if ($servizioFinanziario->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($servizioFinanziario->motivo_ko && strlen($servizioFinanziario->motivo_ko) < 70) {

                /*  $tab = TabMotivoKo::firstOrNew(['nome' => $servizioFinanziario->motivo_ko, 'tipo' => 'servizio-finanziario']);
                  if ($tab->conteggio) {
                      $tab->conteggio = $tab->conteggio + 1;
                  } else {
                      $tab->conteggio = 1;
                  }
                  $tab->save();*/
            }
        }
        $records = Comparasemplice::with('esito')
            ->with('agente')
            ->withCount('allegati')->where('id', $servizioFinanziario->id)->get();


        if ($servizioFinanziario->wasChanged(['esito_id'])) {
            $esito = EsitoComparasemplice::find($servizioFinanziario->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($servizioFinanziario) {
                    $agente = $servizioFinanziario->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaAgenteCambioEsitoComparasemplice($servizioFinanziario));
                    }
                })->afterResponse();

            }

        }


        $view = 'Backend.Comparasemplice.tbody';

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => ContrattoTelefoniaController::class,
                'puoModificare' => Auth::user()->hasPermissionTo('admin')
            ]))
        ];
    }


    /**
     * @param Comparasemplice $model
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
            'cellulare' => '',
            'tipo_segnalazione' => '',
            'uid' => '',

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
        return \App\Models\Comparasemplice::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'nome' => ['required', 'max:255'],
            'cellulare' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['required', 'max:255'],
            'tipo_segnalazione' => ['required', 'max:255'],
        ];

        return $rules;
    }

}

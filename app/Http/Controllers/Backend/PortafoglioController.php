<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimentoPortafoglio;
use DB;

class PortafoglioController extends Controller
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

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Portafoglio.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.Portafoglio.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco movimenti',
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Carica ' . \App\Models\MovimentoPortafoglio::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\MovimentoPortafoglio::query();
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $record = new MovimentoPortafoglio();
        $intent = Auth::user()->createSetupIntent([
            'payment_method_types' => ['card', 'bancontact']
        ]);
        return view('Backend.Portafoglio.edit', [
            'record' => $record,
            'titoloPagina' => 'Carica ' . MovimentoPortafoglio::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco movimenti'],
            'intent' => $intent

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
        $record = new MovimentoPortafoglio();
        $this->salvaDati($record, $request);
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
        $record = MovimentoPortafoglio::find($id);
        abort_if(!$record, 404, 'Questo portafoglio non esiste');
        return view('Backend.Portafoglio.show', [
            'record' => $record,
            'controller' => PortafoglioController::class,
            'titoloPagina' => MovimentoPortafoglio::NOME_SINGOLARE,
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco ' . MovimentoPortafoglio::NOME_PLURALE]

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
        $record = MovimentoPortafoglio::find($id);
        abort_if(!$record, 404, 'Questo portafoglio non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.Portafoglio.edit', [
            'record' => $record,
            'controller' => PortafoglioController::class,
            'titoloPagina' => 'Modifica ' . MovimentoPortafoglio::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco ' . MovimentoPortafoglio::NOME_PLURALE]

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
        $record = MovimentoPortafoglio::find($id);
        abort_if(!$record, 404, 'Questo ' . MovimentoPortafoglio::NOME_SINGOLARE . ' non esiste');
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
        $record = MovimentoPortafoglio::find($id);
        abort_if(!$record, 404, 'Questo portafoglio non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([PortafoglioController::class, 'index']),
        ];
    }

    /**
     * @param MovimentoPortafoglio $model
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
            'agente_id' => '',
            'importo' => 'app\getInputNumero',
            'descrizione' => '',
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
        return \App\Models\MovimentoPortafoglio::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'agente_id' => ['required'],
            'importo' => ['required'],
            'descrizione' => ['required', 'max:255'],
        ];

        return $rules;
    }

}

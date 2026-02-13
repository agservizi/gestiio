<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TipoCafPatronato;
use DB;

class TipoCafPatronatoController extends Controller
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
                return $q->orderBy('nome');
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

        $records = $recordsQB->paginate(50)->withQueryString();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.TipoCafPatronato.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];
        }

        return view('Backend.TipoCafPatronato.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\TipoCafPatronato::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => null,//$ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\TipoCafPatronato::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\TipoCafPatronato::query();
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
        $record = new TipoCafPatronato();
        $record->tipo = 'caf';
        return view('Backend.TipoCafPatronato.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . TipoCafPatronato::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([TipoCafPatronatoController::class, 'index']) => 'Torna a elenco ' . TipoCafPatronato::NOME_PLURALE]

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
        $record = new TipoCafPatronato();
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
        $record = TipoCafPatronato::find($id);
        abort_if(!$record, 404, 'Questo tipocafpatronato non esiste');
        return view('Backend.TipoCafPatronato.show', [
            'record' => $record,
            'controller' => TipoCafPatronatoController::class,
            'titoloPagina' => TipoCafPatronato::NOME_SINGOLARE,
            'breadcrumbs' => [action([TipoCafPatronatoController::class, 'index']) => 'Torna a elenco ' . TipoCafPatronato::NOME_PLURALE]

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
        $record = TipoCafPatronato::find($id);
        abort_if(!$record, 404, 'Questo tipocafpatronato non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.TipoCafPatronato.edit', [
            'record' => $record,
            'controller' => TipoCafPatronatoController::class,
            'titoloPagina' => 'Modifica ' . TipoCafPatronato::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([TipoCafPatronatoController::class, 'index']) => 'Torna a elenco ' . TipoCafPatronato::NOME_PLURALE]

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
        $record = TipoCafPatronato::find($id);
        abort_if(!$record, 404, 'Questo ' . TipoCafPatronato::NOME_SINGOLARE . ' non esiste');
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
        $record = TipoCafPatronato::find($id);
        abort_if(!$record, 404, 'Questo tipocafpatronato non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([TipoCafPatronatoController::class, 'index']),
        ];
    }

    /**
     * @param TipoCafPatronato $model
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
            'nome' => 'app\getInputUcwords',
            'prezzo_cliente' => 'app\getInputNumero',
            'prezzo_agente' => 'app\getInputNumero',
            'tipo' => '',
            'html' => '',
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
        return \App\Models\TipoCafPatronato::get();
    }


    protected function rules($id = null)
    {

        $rules = [
            'nome' => ['required', 'max:255'],
            'model' => ['nullable', 'max:255'],
            'view' => ['nullable', 'max:255'],
            'prezzo_cliente' => ['required'],
            'prezzo_agente' => ['required'],
            'tipo' => ['required', 'max:255'],
        ];

        return $rules;
    }

}

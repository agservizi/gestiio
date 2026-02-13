<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EsitoComparasemplice;
use DB;

class EsitoComparasempliceController extends Controller
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
                'html' => base64_encode(view('Backend.EsitoComparasemplice.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.EsitoComparasemplice.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoComparasemplice::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\EsitoComparasemplice::NOME_SINGOLARE,
            'testoCerca' => null

        ]);

        return view('Backend.EsitoComparasemplice.index', [
            'records' => $this->queryBuilderIndex(),
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoComparasemplice::NOME_PLURALE,
            'testoNuovo' => 'Nuovo ' . \App\Models\EsitoComparasemplice::NOME_SINGOLARE,
            'testoCerca' => null
        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\EsitoComparasemplice::query();
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
        $record = new EsitoComparasemplice();
        return view('Backend.EsitoComparasemplice.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . EsitoComparasemplice::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([EsitoComparasempliceController::class, 'index']) => 'Torna a elenco ' . EsitoComparasemplice::NOME_PLURALE]

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
        $record = new EsitoComparasemplice();
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
        $record = EsitoComparasemplice::find($id);
        abort_if(!$record, 404, 'Questo EsitoComparasemplice non esiste');
        return view('Backend.EsitoComparasemplice.show', [
            'record' => $record,
            'controller' => EsitoComparasempliceController::class,
            'titoloPagina' => EsitoComparasemplice::NOME_SINGOLARE,
            'breadcrumbs' => [action([EsitoComparasempliceController::class, 'index']) => 'Torna a elenco ' . EsitoComparasemplice::NOME_PLURALE]

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
        $record = EsitoComparasemplice::find($id);
        abort_if(!$record, 404, 'Questo EsitoComparasemplice non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.EsitoComparasemplice.edit', [
            'record' => $record,
            'controller' => EsitoComparasempliceController::class,
            'titoloPagina' => 'Modifica ' . EsitoComparasemplice::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([EsitoComparasempliceController::class, 'index']) => 'Torna a elenco ' . EsitoComparasemplice::NOME_PLURALE]

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
        $record = EsitoComparasemplice::find($id);
        abort_if(!$record, 404, 'Questo ' . EsitoComparasemplice::NOME_SINGOLARE . ' non esiste');
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
        $record = EsitoComparasemplice::find($id);
        abort_if(!$record, 404, 'Questo EsitoComparasemplice non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([EsitoComparasempliceController::class, 'index']),
        ];
    }

    /**
     * @param EsitoComparasemplice $model
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
            'colore_hex' => '',
            'chiedi_motivo' => 'app\getInputCheckbox',
            'notifica_mail' => 'app\getInputCheckbox',
            'attivo' => 'app\getInputCheckbox',
            'esito_finale' => '',
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
        return \App\Models\EsitoComparasemplice::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'colore_hex' => ['nullable', 'max:255'],
            'chiedi_motivo' => ['nullable'],
            'notifica_mail' => ['nullable'],
            'attivo' => ['nullable'],
            'esito_finale' => ['nullable', 'max:255'],
        ];

        return $rules;
    }

}

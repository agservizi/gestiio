<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Gestore;
use App\Models\TipoContratto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Listino;
use DB;

class ListinoController extends Controller
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

            'nome' => ['testo' => 'Nome', 'filtro' => function ($q) {
                return $q->orderBy('nome');
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
                'html' => base64_encode(view('Backend.Listino.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];
        }


        return view('Backend.Listino.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Listino::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\Listino::NOME_SINGOLARE,
            'testoCerca' => null

        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Listino::query();
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
        $record = new Listino();
        $record->attivo = 1;
        return view('Backend.Listino.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Listino::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ListinoController::class, 'index']) => 'Torna a elenco ' . Listino::NOME_PLURALE]

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
        $record = new Listino();
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
        $record = Listino::find($id);
        abort_if(!$record, 404, 'Questo listino non esiste');
        $records = TipoContratto::with('gestore')
            ->whereHas('gestore', function ($q) {
                $q->where('attivo', 1);
            })
            ->orderBy(Gestore::select('nome')->whereColumn('tab_gestori.id', 'tipi_contratto.gestore_id'))->orderBy('tipi_contratto.nome')
            ->select('tipi_contratto.*')
            ->get();

        return view('Backend.Listino.show', [
            'record' => $record,
            'records' => $records,
            'controller' => ListinoController::class,
            'titoloPagina' => Listino::NOME_SINGOLARE,
            'breadcrumbs' => [action([ListinoController::class, 'index']) => 'Torna a elenco ' . Listino::NOME_PLURALE]

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
        $record = Listino::withCount('agenti')->find($id);
        abort_if(!$record, 404, 'Questo listino non esiste');
        if ($record->agenti_count) {
            $eliminabile = 'Non eliminabile perchè usato da '.$record->agenti_count.' agenti';
        } else {
            $eliminabile = true;
        }
        return view('Backend.Listino.edit', [
            'record' => $record,
            'controller' => ListinoController::class,
            'titoloPagina' => 'Modifica ' . Listino::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ListinoController::class, 'index']) => 'Torna a elenco ' . Listino::NOME_PLURALE]

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
        $record = Listino::find($id);
        abort_if(!$record, 404, 'Questo ' . Listino::NOME_SINGOLARE . ' non esiste');
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
        $record = Listino::find($id);
        abort_if(!$record, 404, 'Questo listino non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ListinoController::class, 'index']),
        ];
    }

    /**
     * @param Listino $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->prodotto = 'contratto-telefonia';
        }

        //Ciclo su campi
        $campi = [
            'nome' => 'app\getInputUcwords',
            'attivo' => 'app\getInputCheckbox',
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
        return \App\Models\Listino::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'attivo' => ['nullable'],
        ];

        return $rules;
    }

}

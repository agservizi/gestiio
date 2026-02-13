<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ListinoBrtEuropa;
use DB;

class ListinoBrtEuropaController extends Controller
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


        $records = $recordsQB->get();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.ListinoBrtEuropa.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }



        return view('Backend.ListinoBrtEuropa.index', [
            'records' => $records,
            'recordsMulti' => $this->applicaFiltriMulti($request)->get(),
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ListinoBrtEuropa::NOME_PLURALE,
            'orderBy' => null,
            'ordinamenti' => null,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\ListinoBrtEuropa::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ListinoBrtEuropa::query()
            ->orderBy('da_peso')->where('tipo', 'mono');
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
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltriMulti($request)
    {

        $queryBuilder = \App\Models\ListinoBrtEuropa::query()
            ->orderBy('da_peso')->where('tipo', '=', 'multi');
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
        $record = new ListinoBrtEuropa();
        return view('Backend.ListinoBrtEuropa.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ListinoBrtEuropa::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ListinoBrtEuropaController::class, 'index']) => 'Torna a elenco ' . ListinoBrtEuropa::NOME_PLURALE]

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
        $record = new ListinoBrtEuropa();
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
        $record = ListinoBrtEuropa::find($id);
        abort_if(!$record, 404, 'Questo listinobrteuropa non esiste');
        return view('Backend.ListinoBrtEuropa.show', [
            'record' => $record,
            'controller' => ListinoBrtEuropaController::class,
            'titoloPagina' => ListinoBrtEuropa::NOME_SINGOLARE,
            'breadcrumbs' => [action([ListinoBrtEuropaController::class, 'index']) => 'Torna a elenco ' . ListinoBrtEuropa::NOME_PLURALE]

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
        $record = ListinoBrtEuropa::find($id);
        abort_if(!$record, 404, 'Questo listinobrteuropa non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.ListinoBrtEuropa.edit', [
            'record' => $record,
            'controller' => ListinoBrtEuropaController::class,
            'titoloPagina' => 'Modifica ' . ListinoBrtEuropa::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ListinoBrtEuropaController::class, 'index']) => 'Torna a elenco ' . ListinoBrtEuropa::NOME_PLURALE]

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
        $record = ListinoBrtEuropa::find($id);
        abort_if(!$record, 404, 'Questo ' . ListinoBrtEuropa::NOME_SINGOLARE . ' non esiste');
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
        $record = ListinoBrtEuropa::find($id);
        abort_if(!$record, 404, 'Questo listinobrteuropa non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ListinoBrtEuropaController::class, 'index']),
        ];
    }

    /**
     * @param ListinoBrtEuropa $model
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
            'da_peso' => 'app\getInputNumeroZero',
            'a_peso' => 'app\getInputNumero',
            'gruppo_a' => 'app\getInputNumero',
            'gruppo_b' => 'app\getInputNumero',
            'gruppo_c' => 'app\getInputNumero',
            'gruppo_d' => 'app\getInputNumero',
            'tipo' => '',
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
        return \App\Models\ListinoBrtEuropa::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'da_peso' => ['required'],
            'a_peso' => ['nullable'],
            'gruppo_a' => ['required'],
            'gruppo_b' => ['required'],
            'gruppo_c' => ['required'],
            'gruppo_d' => ['required'],
        ];

        return $rules;
    }

}

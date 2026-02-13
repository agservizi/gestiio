<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ListinoBrt;
use DB;

class ListinoBrtController extends Controller
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


        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.ListinoBrt.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.ListinoBrt.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ListinoBrt::NOME_PLURALE,
            'orderBy' => null,//,
            'ordinamenti' => null,// $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\ListinoBrt::NOME_SINGOLARE,
            'testoCerca' => null

        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ListinoBrt::query()->orderBy('da_peso');
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
        $record = new ListinoBrt();
        return view('Backend.ListinoBrt.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ListinoBrt::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ListinoBrtController::class, 'index']) => 'Torna a elenco ' . ListinoBrt::NOME_PLURALE]

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
        $record = new ListinoBrt();
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
        $record = ListinoBrt::find($id);
        abort_if(!$record, 404, 'Questo listinobrt non esiste');
        return view('Backend.ListinoBrt.show', [
            'record' => $record,
            'controller' => ListinoBrtController::class,
            'titoloPagina' => ListinoBrt::NOME_SINGOLARE,
            'breadcrumbs' => [action([ListinoBrtController::class, 'index']) => 'Torna a elenco ' . ListinoBrt::NOME_PLURALE]

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
        $record = ListinoBrt::find($id);
        abort_if(!$record, 404, 'Questo listinobrt non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.ListinoBrt.edit', [
            'record' => $record,
            'controller' => ListinoBrtController::class,
            'titoloPagina' => 'Modifica ' . ListinoBrt::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ListinoBrtController::class, 'index']) => 'Torna a elenco ' . ListinoBrt::NOME_PLURALE]

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
        $record = ListinoBrt::find($id);
        abort_if(!$record, 404, 'Questo ' . ListinoBrt::NOME_SINGOLARE . ' non esiste');
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
        $record = ListinoBrt::find($id);
        abort_if(!$record, 404, 'Questo listinobrt non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ListinoBrtController::class, 'index']),
        ];
    }

    /**
     * @param ListinoBrt $model
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
            'da_peso' => 'app\getInputNumero',
            'a_peso' => 'app\getInputNumero',
            'home_delivery' => 'app\getInputNumero',
            'brt_fermopoint' => 'app\getInputNumero',
            'contrassegno' => 'app\getInputNumero',
            'al_kg' => 'app\getInputCheckbox',
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
        return \App\Models\ListinoBrt::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'da_peso' => ['required'],
            'a_peso' => ['nullable'],
            'home_delivery' => ['nullable'],
            'brt_fermopoint' => ['nullable'],
            'al_kg' => ['nullable'],
        ];

        return $rules;
    }

}

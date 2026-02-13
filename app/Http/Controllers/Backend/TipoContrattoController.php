<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TipoContratto;
use DB;
use function App\getInputCheckbox;

class TipoContrattoController extends Controller
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
                'html' => base64_encode(view('Backend.TipoContratto.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.TipoContratto.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\TipoContratto::NOME_PLURALE,
            'ordinamenti' => null,//$ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\TipoContratto::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\TipoContratto::with('gestore')->orderBy('nome');
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
        $record = new TipoContratto();
        $record->attivo = 1;
        return view('Backend.TipoContratto.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . TipoContratto::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([TipoContrattoController::class, 'index']) => 'Torna a elenco ' . TipoContratto::NOME_PLURALE]

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
        $record = new TipoContratto();
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = TipoContratto::withCount('contratti')->find($id);
        abort_if(!$record, 404, 'Questo tipocontratto non esiste');
        if ($record->contratti_count) {
            $eliminabile = 'Non eliminabile perchè ha ' . $record->contratti_count . ' contratti';
        } else {
            $eliminabile = true;
        }
        return view('Backend.TipoContratto.edit', [
            'record' => $record,
            'controller' => TipoContrattoController::class,
            'titoloPagina' => 'Modifica ' . TipoContratto::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([TipoContrattoController::class, 'index']) => 'Torna a elenco ' . TipoContratto::NOME_PLURALE]

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
        $record = TipoContratto::find($id);
        abort_if(!$record, 404, 'Questo ' . TipoContratto::NOME_SINGOLARE . ' non esiste');
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
        $record = TipoContratto::find($id);
        abort_if(!$record, 404, 'Questo tipocontratto non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([TipoContrattoController::class, 'index']),
        ];
    }

    /**
     * @param TipoContratto $model
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
            'gestore_id' => '',
            'email_notifica_gestore' => 'strtolower',
            'durata_contratto' => 'app\getInputNumero',
            'attivo' => 'app\getInputCheckbox',
            'prodotto' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        if (Auth::id() == 1) {
            $model->pda = $request->input('pda');
            $model->crea_in_bozza = getInputCheckbox($request->input('crea_in_bozza'));
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
        return \App\Models\TipoContratto::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'gestore_id' => ['required'],
            'attivo' => ['nullable'],
            'email_notifica_gestore' => ['nullable', 'email'],
        ];

        return $rules;
    }

}

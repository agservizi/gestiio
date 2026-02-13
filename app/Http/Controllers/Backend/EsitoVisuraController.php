<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Visura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EsitoVisura;
use DB;

class EsitoVisuraController extends Controller
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
                'html' => base64_encode(view('Backend.EsitoVisura.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.EsitoVisura.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoVisura::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\EsitoVisura::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\EsitoVisura::query();
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
        $record = new EsitoVisura();
        $record->attivo = 1;
        return view('Backend.EsitoVisura.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . EsitoVisura::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([EsitoVisuraController::class, 'index']) => 'Torna a elenco ' . EsitoVisura::NOME_PLURALE]

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
        $record = new EsitoVisura();
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
        $record = EsitoVisura::find($id);
        abort_if(!$record, 404, 'Questo esitovisura non esiste');
        return view('Backend.EsitoVisura.show', [
            'record' => $record,
            'controller' => EsitoVisuraController::class,
            'titoloPagina' => EsitoVisura::NOME_SINGOLARE,
            'breadcrumbs' => [action([EsitoVisuraController::class, 'index']) => 'Torna a elenco ' . EsitoVisura::NOME_PLURALE]

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
        $record = EsitoVisura::find($id);
        abort_if(!$record, 404, 'Questo esitovisura non esiste');
        if (Visura::where('esito_id', $id)->exists()) {
            $eliminabile = 'Non è possibile eliminare questo esito';
        } else {
            $eliminabile = true;
        }
        return view('Backend.EsitoVisura.edit', [
            'record' => $record,
            'controller' => EsitoVisuraController::class,
            'titoloPagina' => 'Modifica ' . EsitoVisura::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([EsitoVisuraController::class, 'index']) => 'Torna a elenco ' . EsitoVisura::NOME_PLURALE]

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
        $record = EsitoVisura::find($id);
        abort_if(!$record, 404, 'Questo ' . EsitoVisura::NOME_SINGOLARE . ' non esiste');
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
        $record = EsitoVisura::find($id);
        abort_if(!$record, 404, 'Questo esitovisura non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([EsitoVisuraController::class, 'index']),
        ];
    }

    /**
     * @param EsitoVisura $model
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
        return \App\Models\EsitoVisura::get();
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

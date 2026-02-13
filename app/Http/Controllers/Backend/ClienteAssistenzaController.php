<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClienteAssistenza;
use DB;

class ClienteAssistenzaController extends Controller
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
                'html' => base64_encode(view('Backend.ClienteAssistenza.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.ClienteAssistenza.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ClienteAssistenza::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\ClienteAssistenza::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nominativo, codice fiscale'

        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ClienteAssistenza::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,codice_fiscale)'), 'like', "%$t%");
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
        $record = new ClienteAssistenza();
        return view('Backend.ClienteAssistenza.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ClienteAssistenza::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco ' . ClienteAssistenza::NOME_PLURALE]

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
        $record = new ClienteAssistenza();
        $this->salvaDati($record, $request);
        return redirect()->action([RichiestaAssistenzaController::class, 'create'], ['cliente_id' => $record->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = ClienteAssistenza::find($id);
        abort_if(!$record, 404, 'Questo clienteassistenza non esiste');
        return view('Backend.ClienteAssistenza.show', [
            'record' => $record,
            'controller' => ClienteAssistenzaController::class,
            'titoloPagina' => ClienteAssistenza::NOME_SINGOLARE,
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco ' . ClienteAssistenza::NOME_PLURALE]

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
        $record = ClienteAssistenza::find($id);
        abort_if(!$record, 404, 'Questo clienteassistenza non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.ClienteAssistenza.edit', [
            'record' => $record,
            'controller' => ClienteAssistenzaController::class,
            'titoloPagina' => 'Modifica ' . ClienteAssistenza::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco ' . ClienteAssistenza::NOME_PLURALE]

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
        $record = ClienteAssistenza::find($id);
        abort_if(!$record, 404, 'Questo ' . ClienteAssistenza::NOME_SINGOLARE . ' non esiste');
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
        $record = ClienteAssistenza::find($id);
        abort_if(!$record, 404, 'Questo clienteassistenza non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ClienteAssistenzaController::class, 'index']),
        ];
    }

    /**
     * @param ClienteAssistenza $model
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
            'cognome' => 'app\getInputUcwords',
            'codice_fiscale' => 'strtoupper',
            'email' => 'strtolower',
            'telefono' => 'app\getInputTelefono',
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
        return \App\Models\ClienteAssistenza::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'codice_fiscale' => ['required', new \App\Rules\CodiceFiscaleRule()],
            'email' => ['nullable', 'max:255'],
            'telefono' => ['nullable', new \App\Rules\TelefonoRule()],
        ];

        return $rules;
    }

}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContattoBrt;
use DB;

class ContattoBrtController extends Controller
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
                'html' => base64_encode(view('Backend.ContattoBrt.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.ContattoBrt.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\ContattoBrt::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null,//'Nuovo '. \App\Models\ContattoBrt::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in ragione sociale'

        ]);

        return view('Backend.ContattoBrt.index', [
            'records' => $this->queryBuilderIndex(),
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . \App\Models\ContattoBrt::NOME_PLURALE,
            'testoNuovo' => 'Nuovo ' . \App\Models\ContattoBrt::NOME_SINGOLARE,
            'testoCerca' => null
        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\ContattoBrt::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',ragione_sociale_destinatario)'), 'like', "%$t%");
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
        $record = new ContattoBrt();
        return view('Backend.ContattoBrt.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ContattoBrt::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ContattoBrtController::class, 'index']) => 'Torna a elenco ' . ContattoBrt::NOME_PLURALE]

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
        $record = new ContattoBrt();
        $record->agente_id = Auth::id();
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
        $record = ContattoBrt::find($id);
        abort_if(!$record, 404, 'Questo contattobrt non esiste');
        return view('Backend.ContattoBrt.show', [
            'record' => $record,
            'controller' => ContattoBrtController::class,
            'titoloPagina' => ContattoBrt::NOME_SINGOLARE,
            'breadcrumbs' => [action([ContattoBrtController::class, 'index']) => 'Torna a elenco ' . ContattoBrt::NOME_PLURALE]

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
        $record = ContattoBrt::find($id);
        abort_if(!$record, 404, 'Questo contattobrt non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.ContattoBrt.edit', [
            'record' => $record,
            'controller' => ContattoBrtController::class,
            'titoloPagina' => 'Modifica ' . ContattoBrt::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ContattoBrtController::class, 'index']) => 'Torna a elenco ' . ContattoBrt::NOME_PLURALE]

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
        $record = ContattoBrt::find($id);
        abort_if(!$record, 404, 'Questo ' . ContattoBrt::NOME_SINGOLARE . ' non esiste');
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
        $record = ContattoBrt::find($id);
        abort_if(!$record, 404, 'Questo contattobrt non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ContattoBrtController::class, 'index']),
        ];
    }

    /**
     * @param ContattoBrt $model
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
            'ragione_sociale_destinatario' => '',
            'indirizzo_destinatario' => '',
            'cap_destinatario' => '',
            'localita_destinazione' => '',
            'provincia_destinatario' => '',
            'nazione_destinazione' => '',
            'mobile_referente_consegna' => '',
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
        return \App\Models\ContattoBrt::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'ragione_sociale_destinatario' => ['required'],
            'indirizzo_destinatario' => ['nullable'],
            'cap_destinatario' => ['nullable'],
            'localita_destinazione' => ['nullable'],
            'provincia_destinatario' => ['nullable'],
            'nazione_destinazione' => ['nullable'],
            'mobile_referente_consegna' => ['nullable'],
        ];

        return $rules;
    }

}

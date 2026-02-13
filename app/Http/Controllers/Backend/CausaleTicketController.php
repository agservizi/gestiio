<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CausaleTicket;
use DB;

class CausaleTicketController extends Controller
{
    protected $conFiltro = false;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $servizioType = $request->input('servizio_type', array_keys(CausaleTicket::SERVIZI)[0]);

        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request, $servizioType);

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
                'html' => base64_encode(view('Backend.CausaleTicket.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.CausaleTicket.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\CausaleTicket::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => null,//$ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\CausaleTicket::NOME_SINGOLARE,
            'testoCerca' => null,
            'servizioType' => $servizioType,

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request, $servizioType)
    {

        $queryBuilder = \App\Models\CausaleTicket::where('servizio_type', $servizioType);
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
        $record = new CausaleTicket();
        $record->servizio_type = \request()->input('servizio_type');
        return view('Backend.CausaleTicket.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . CausaleTicket::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([CausaleTicketController::class, 'index']) => 'Torna a elenco ' . CausaleTicket::NOME_PLURALE]

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
        $record = new CausaleTicket();
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
        $record = CausaleTicket::find($id);
        abort_if(!$record, 404, 'Questa causaleticket non esiste');
        return view('Backend.CausaleTicket.show', [
            'record' => $record,
            'controller' => CausaleTicketController::class,
            'titoloPagina' => CausaleTicket::NOME_SINGOLARE,
            'breadcrumbs' => [action([CausaleTicketController::class, 'index']) => 'Torna a elenco ' . CausaleTicket::NOME_PLURALE]

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
        $record = CausaleTicket::find($id);
        abort_if(!$record, 404, 'Questa causaleticket non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.CausaleTicket.edit', [
            'record' => $record,
            'controller' => CausaleTicketController::class,
            'titoloPagina' => 'Modifica ' . CausaleTicket::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([CausaleTicketController::class, 'index']) => 'Torna a elenco ' . CausaleTicket::NOME_PLURALE]

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
        $record = CausaleTicket::find($id);
        abort_if(!$record, 404, 'Questa ' . CausaleTicket::NOME_SINGOLARE . ' non esiste');
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
        $record = CausaleTicket::find($id);
        abort_if(!$record, 404, 'Questa causaleticket non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([CausaleTicketController::class, 'index']),
        ];
    }

    /**
     * @param CausaleTicket $model
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
            'servizio_type' => '',
            'descrizione_causale' => '',
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
        return \App\Models\CausaleTicket::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'servizio_type' => ['required', 'max:255'],
            'descrizione_causale' => ['required', 'max:255'],
        ];

        return $rules;
    }

}

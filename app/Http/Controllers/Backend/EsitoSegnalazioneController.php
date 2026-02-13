<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EsitoSegnalazione;
use DB;

class EsitoSegnalazioneController extends Controller
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
                'html' => base64_encode(view('Backend.EsitoSegnalazione.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.EsitoSegnalazione.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoSegnalazione::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo'=>'Nuovo '. \App\Models\EsitoSegnalazione::NOME_SINGOLARE,
            'testoCerca'=>null

        ]);
        
        return view('Backend.EsitoSegnalazione.index', [
            'records' => $this->queryBuilderIndex(),
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoSegnalazione::NOME_PLURALE,
            'testoNuovo'=>'Nuovo '. \App\Models\EsitoSegnalazione::NOME_SINGOLARE,
            'testoCerca'=>null
        ]);


    }
      
            /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder =  \App\Models\EsitoSegnalazione::query();
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
                $record=new EsitoSegnalazione();
        return view('Backend.EsitoSegnalazione.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . EsitoSegnalazione::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([EsitoSegnalazioneController::class, 'index']) => 'Torna a elenco ' . EsitoSegnalazione::NOME_PLURALE]

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
                $request->validate($this->rules(null));
        $record=new EsitoSegnalazione();
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
                $record = EsitoSegnalazione::find($id);
        abort_if(!$record, 404, 'Questo esitosegnalazione non esiste');
        return view('Backend.EsitoSegnalazione.show', [
            'record' => $record,
            'controller' => EsitoSegnalazioneController::class,
            'titoloPagina' =>  EsitoSegnalazione::NOME_SINGOLARE,
            'breadcrumbs' => [action([EsitoSegnalazioneController::class, 'index']) => 'Torna a elenco ' . EsitoSegnalazione::NOME_PLURALE]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
                $record = EsitoSegnalazione::find($id);
        abort_if(!$record, 404, 'Questo esitosegnalazione non esiste');
         if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.EsitoSegnalazione.edit', [
            'record' => $record,
            'controller' => EsitoSegnalazioneController::class,
            'titoloPagina' => 'Modifica ' . EsitoSegnalazione::NOME_SINGOLARE,
            'eliminabile'=>$eliminabile,
            'breadcrumbs' => [action([EsitoSegnalazioneController::class, 'index']) => 'Torna a elenco ' . EsitoSegnalazione::NOME_PLURALE]

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
                $record = EsitoSegnalazione::find($id);
        abort_if(!$record, 404, 'Questo ' . EsitoSegnalazione::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
                $record = EsitoSegnalazione::find($id);
        abort_if(!$record, 404, 'Questo esitosegnalazione non esiste');
        
        $record->delete();
        

        return [
            'success' => true,
            'redirect' => action([EsitoSegnalazioneController::class,'index']),
        ];
    }

    /**
     * @param EsitoSegnalazione $model
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
            'nome'=>'app\getInputUcwords',
            'colore_hex'=>'',
            'chiedi_motivo'=>'app\getInputCheckbox',
            'notifica_mail'=>'app\getInputCheckbox',
            'attivo'=>'app\getInputCheckbox',
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
        return \App\Models\EsitoSegnalazione::get();
    }



    protected function rules($id=null)
    {


        $rules=[
     'nome'=>['required','max:255'],
     'colore_hex'=>['nullable','max:255'],
     'chiedi_motivo'=>['nullable'],
     'notifica_mail'=>['nullable'],
     'attivo'=>['nullable'],
];

        return $rules;
    }

}

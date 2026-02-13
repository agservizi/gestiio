<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\AttivazioneSim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SostituzioneSim;
use DB;

class SostituzioneSimController extends Controller
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
                'html' => base64_encode(view('Backend.SostituzioneSim.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.SostituzioneSim.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\SostituzioneSim::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\SostituzioneSim::NOME_SINGOLARE,
            'testoCerca' => null

        ]);

        return view('Backend.SostituzioneSim.index', [
            'records' => $this->queryBuilderIndex(),
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . \App\Models\SostituzioneSim::NOME_PLURALE,
            'testoNuovo' => 'Nuovo ' . \App\Models\SostituzioneSim::NOME_SINGOLARE,
            'testoCerca' => null
        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\SostituzioneSim::query();
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
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new SostituzioneSim();
        $this->salvaDati($record, $request);
        $datiRitorno = new DatiRitorno();
        $datiRitorno->chiudiDialog(true)->redirect(action([AttivazioneSimController::class, 'show'], $record->attivazione_sim_id));

        return $datiRitorno->getArray();

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
        $record = SostituzioneSim::find($id);
        abort_if(!$record, 404, 'Questo sostituzionesim non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.SostituzioneSim.edit', [
            'record' => $record,
            'controller' => SostituzioneSimController::class,
            'titoloPagina' => 'Modifica ' . SostituzioneSim::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([SostituzioneSimController::class, 'index']) => 'Torna a elenco ' . SostituzioneSim::NOME_PLURALE]

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
        $record = SostituzioneSim::find($id);
        abort_if(!$record, 404, 'Questo ' . SostituzioneSim::NOME_SINGOLARE . ' non esiste');
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
        $record = SostituzioneSim::find($id);
        abort_if(!$record, 404, 'Questo sostituzionesim non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([SostituzioneSimController::class, 'index']),
        ];
    }

    /**
     * @param SostituzioneSim $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

            $model->agente_id = Auth::id();
            $attivazione = AttivazioneSim::find($request->input('attivazione_sim_id'));
            $model->seriale_vecchia_sim = $attivazione->codice_sim;
            $model->importo = 5;
        }

        //Ciclo su campi
        $campi = [
            'attivazione_sim_id' => '',
            'motivazione' => '',
            'seriale_nuova_sim' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        $attivazione->codice_sim = $model->seriale_nuova_sim;
        $attivazione->save();

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
        return \App\Models\SostituzioneSim::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'attivazione_sim_id' => ['required'],
            'motivazione' => ['required', 'max:255'],
            'seriale_nuova_sim' => ['required', 'max:255'],
        ];

        return $rules;
    }

}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProduzioneOperatore;
use App\Models\RigaFatturaProforma;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FatturaProforma;
use DB;
use Illuminate\Support\Str;
use PDF;

class FatturaProformaController extends Controller
{
    protected $conFiltro = false;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        //Aggiusta

        foreach (RigaFatturaProforma::where('classe', 'App\Models\Contratto')->get() as $riga) {
            $riga->classe = 'App\Models\ContrattoTelefonia';
            $riga->save();
        }


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
                'html' => base64_encode(view('Backend.FatturaProforma.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.FatturaProforma.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\FatturaProforma::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => null,//$ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null,//'Nuova ' . \App\Models\FatturaProforma::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\FatturaProforma::query()
            ->with('intestazione:id,denominazione');
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
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = FatturaProforma::with('intestazione')->with('righe')->find($id);
        abort_if(!$record, 404, 'Questa fattura proforma non esiste');
        return view('Backend.FatturaProforma.show', [
            'record' => $record,
            'controller' => FatturaProformaController::class,
            'titoloPagina' => ucfirst(FatturaProforma::NOME_SINGOLARE) . ' #' . $record->numero,
            'breadcrumbs' => [action([FatturaProformaController::class, 'index']) => 'Torna a elenco ' . FatturaProforma::NOME_PLURALE]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function pdf($id)
    {
        $record = FatturaProforma::find($id);
        abort_if(!$record, 404, 'Questa fatturaproforma non esiste');


        $pdf = PDF::loadView('Backend.FatturaProforma.pdf', [
            'record' => $record,
        ]);


        return $pdf->stream('aa.pdf');


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
        $record = FatturaProforma::find($id);
        abort_if(!$record, 404, 'Questa ' . FatturaProforma::NOME_SINGOLARE . ' non esiste');
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

        $record = FatturaProforma::find($id);
        abort_if(!$record, 404, 'Questa fatturaproforma non esiste');
        $produzione = ProduzioneOperatore::firstWhere('fattura_proforma_id', $id);
        if ($produzione) {
            $produzione->fattura_proforma_id = null;
            $produzione->save();
        }

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([FatturaProformaController::class, 'index']),
        ];
    }

    /**
     * @param FatturaProforma $model
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
            'data' => 'app\getInputData',
            'numero' => '',
            'intestazione_id' => '',
            'totale_imponibile' => 'app\getInputNumero',
            'aliquota_iva' => 'app\getInputNumero',
            'totale_con_iva' => 'app\getInputNumero',
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
        return \App\Models\FatturaProforma::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'data' => ['required'],
            'numero' => ['required'],
            'intestazione_id' => ['required'],
            'totale_imponibile' => ['required'],
            'aliquota_iva' => ['required'],
            'totale_con_iva' => ['required'],
        ];

        return $rules;
    }

}

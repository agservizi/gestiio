<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContrattoTelefonia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EsitoTelefonia;
use DB;

class EsitoTelefoniaController extends Controller
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
                'html' => base64_encode(view('Backend.Esito.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.Esito.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\EsitoTelefonia::NOME_PLURALE,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\EsitoTelefonia::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\EsitoTelefonia::orderBy('nome')->where('id', '<>', 'bozza');

        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $record = new EsitoTelefonia();
        $record->attivo = 1;
        $record->esito_finale = 'in-lavorazione';
        return view('Backend.Esito.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . EsitoTelefonia::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([EsitoTelefoniaController::class, 'index']) => 'Torna a elenco ' . EsitoTelefonia::NOME_PLURALE]

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
        $record = new EsitoTelefonia();
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = EsitoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo esito non esiste');

        if (ContrattoTelefonia::where('esito_id', $id)->exists()) {
            $eliminabile = 'Non è possibile eliminare questo esito';
        } else {
            $eliminabile = true;
        }
        return view('Backend.Esito.edit', [
            'record' => $record,
            'controller' => EsitoTelefoniaController::class,
            'titoloPagina' => 'Modifica ' . EsitoTelefonia::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([EsitoTelefoniaController::class, 'index']) => 'Torna a elenco ' . EsitoTelefonia::NOME_PLURALE]

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
        $record = EsitoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo ' . EsitoTelefonia::NOME_SINGOLARE . ' non esiste');
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
        $record = EsitoTelefonia::find($id);
        abort_if(!$record, 404, 'Questo esito non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([EsitoTelefoniaController::class, 'index']),
        ];
    }

    /**
     * @param EsitoTelefonia $model
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
            'esito_finale' => '',
            'chiedi_motivo' => 'app\getInputCheckbox',
            'notifica_mail' => 'app\getInputCheckbox',
            'attivo' => 'app\getInputCheckbox',
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
        return \App\Models\EsitoTelefonia::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'colore_hex' => ['nullable', 'max:255'],
            'attivo' => ['nullable'],
        ];

        return $rules;
    }

}

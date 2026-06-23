<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ChiamataApi;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ChiamataApiController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return Response
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
            }],

        ];

        $orderByUser = Auth::user()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.ChiamataApi.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])),
            ];

        }

        return view('Backend.ChiamataApi.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.ChiamataApi::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null, // 'Nuova '. \App\Models\ChiamataApi::NOME_SINGOLARE,
            'testoCerca' => null,

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {
        $queryBuilder = ChiamataApi::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome)'), 'like', "%$t%");
            }
        }

        return $queryBuilder;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $record = ChiamataApi::find($id);
        abort_if(! $record, 404, 'Questa chiamataapi non esiste');

        return view('Backend.ChiamataApi.show', [
            'record' => $record,
            'controller' => ChiamataApiController::class,
            'titoloPagina' => ChiamataApi::NOME_SINGOLARE,
            'breadcrumbs' => [action([ChiamataApiController::class, 'index']) => 'Torna a elenco '.ChiamataApi::NOME_PLURALE],

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $record = ChiamataApi::find($id);
        abort_if(! $record, 404, 'Questa '.ChiamataApi::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $record = ChiamataApi::find($id);
        abort_if(! $record, 404, 'Questa chiamataapi non esiste');

        $record->delete();

        return [
            'success' => true,
            'redirect' => action([ChiamataApiController::class, 'index']),
        ];
    }

    /**
     * @param  ChiamataApi  $model
     * @param  Request  $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {

        }

        // Ciclo su campi
        $campi = [
            'servizio' => '',
            'url' => '',
            'method' => '',
            'request' => '',
            'response' => '',
            'status' => '',
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
        return ChiamataApi::get();
    }

    protected function rules($id = null)
    {

        $rules = [
            'servizio' => ['required', 'max:255'],
            'url' => ['required', 'max:255'],
            'method' => ['required', 'max:255'],
            'request' => ['required'],
            'response' => ['nullable'],
            'status' => ['nullable', 'max:255'],
        ];

        return $rules;
    }
}

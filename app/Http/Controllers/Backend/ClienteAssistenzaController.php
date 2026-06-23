<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ClienteAssistenza;
use App\Models\User;
use App\Rules\CodiceFiscaleRule;
use App\Rules\TelefonoRule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClienteAssistenzaController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): View|JsonResponse
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

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $orderByUser = $authUser?->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($authUser instanceof User && $orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => base64_encode(view('Backend.ClienteAssistenza.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
            ]);

        }

        return view('Backend.ClienteAssistenza.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.ClienteAssistenza::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo '.ClienteAssistenza::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nominativo, codice fiscale',

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = ClienteAssistenza::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,codice_fiscale)'), 'like', "%$t%");
            }
        }

        // $this->conFiltro = true;
        return $queryBuilder;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): View
    {
        $record = new ClienteAssistenza;

        return view('Backend.ClienteAssistenza.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo '.ClienteAssistenza::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco '.ClienteAssistenza::NOME_PLURALE],

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        $record = new ClienteAssistenza;
        $this->salvaDati($record, $request);

        return redirect()->action([RichiestaAssistenzaController::class, 'create'], ['cliente_id' => $record->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id): View
    {
        $record = ClienteAssistenza::find($id);
        abort_if(! $record, 404, 'Questo clienteassistenza non esiste');

        return view('Backend.ClienteAssistenza.show', [
            'record' => $record,
            'controller' => ClienteAssistenzaController::class,
            'titoloPagina' => ClienteAssistenza::NOME_SINGOLARE,
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco '.ClienteAssistenza::NOME_PLURALE],

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id): View
    {
        $record = ClienteAssistenza::find($id);
        abort_if(! $record, 404, 'Questo clienteassistenza non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        return view('Backend.ClienteAssistenza.edit', [
            'record' => $record,
            'controller' => ClienteAssistenzaController::class,
            'titoloPagina' => 'Modifica '.ClienteAssistenza::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ClienteAssistenzaController::class, 'index']) => 'Torna a elenco '.ClienteAssistenza::NOME_PLURALE],

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $record = ClienteAssistenza::find($id);
        abort_if(! $record, 404, 'Questo '.ClienteAssistenza::NOME_SINGOLARE.' non esiste');
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
    public function destroy($id): JsonResponse
    {
        $record = ClienteAssistenza::find($id);
        abort_if(! $record, 404, 'Questo clienteassistenza non esiste');

        $record->delete();

        return response()->json([
            'success' => true,
            'redirect' => action([ClienteAssistenzaController::class, 'index']),
        ]);
    }

    /**
     * @param  ClienteAssistenza  $model
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

    protected function backToIndex(): RedirectResponse
    {
        return redirect()->action([get_class($this), 'index']);
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return ClienteAssistenza::get();
    }

    protected function rules($id = null)
    {

        $rules = [
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'codice_fiscale' => ['required', new CodiceFiscaleRule],
            'email' => ['nullable', 'max:255'],
            'telefono' => ['nullable', new TelefonoRule],
        ];

        return $rules;
    }
}

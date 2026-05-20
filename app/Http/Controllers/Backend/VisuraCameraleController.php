<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Funzioni\VisuraCameraleService;
use App\Http\MieClassi\DatiRitorno;
use App\Models\ContrattoTelefonia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VisuraCamerale;
use Illuminate\Support\Facades\DB;

class VisuraCameraleController extends Controller
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

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $orderByUser = $authUser->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } else if ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        //Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.VisuraCamerale.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render())
            ];

        }


        $spesaMensile = VisuraCamerale::whereDate('created_at', '>=', now()->startOfMonth())->whereDate('created_at', '<=', now()->endOfMonth())->sum('prezzo');

        return view('Backend.VisuraCamerale.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . VisuraCamerale::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => null,// $ordinamenti,
            'filtro' => 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null,// 'Nuova ' . \App\Models\VisuraCamerale::NOME_SINGOLARE,
            'testoCerca' => null,
            'spesaMensile' => $spesaMensile

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = VisuraCamerale::query()
            ->with('contratto')
            ->with(['agente' => function ($q) {
                $q->select('id', 'name', 'cognome','ragione_sociale');
            }]);
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
        $record = VisuraCamerale::find($id);
        abort_if(!$record, 404, 'Questa visura camerale non esiste');
        $visuraService = new VisuraCameraleService();

        $res = $visuraService->richiediAllegato($record->richiesta_id, $record->tipo);

        if ($res) {

            return response()->streamDownload(function () use ($res) {
                echo $res->data->file;
            }, $res->data->nome);

        } else {
            return ['success' => false, 'message' => $visuraService->message];
        }


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
        $record = VisuraCamerale::find($id);
        abort_if(!$record, 404, 'Questa ' . VisuraCamerale::NOME_SINGOLARE . ' non esiste');

        $visuraService = new VisuraCameraleService();

        $res = $visuraService->aggiornaVisura($record->richiesta_id, $record->tipo);

        if ($res) {
            $record->stato_richiesta = $res->data->stato_richiesta;
            if ($res->data->allegati) {
                $record->allegati = $res->data->allegati;
            }
            $record->response = $res;
            $record->save();

            /** @var \App\Models\User $authUser */
            $authUser = Auth::user();
            $datiRitorno = new DatiRitorno();
            $datiRitorno->oggettoReload($record->id, view('Backend.VisuraCamerale.tabella', [
                'records' => [$record],
                'visualizzaAgente' => $authUser->hasPermissionTo('admin'),
                'controller' => VisuraCameraleController::class
            ]));
            return $datiRitorno->getArray();

        } else {
            return ['success' => false, 'message' => $visuraService->message];

        }


    }


    public function showCercaAzienda()
    {
        return view('Backend.VisuraCamerale.cercaAzienda', [
            'record' => new ContrattoTelefonia(),
            'titoloPagina' => 'Cerca azienda'
        ]);
    }


    public function postCercaAzienda(Request $request)
    {

        $servece = new VisuraCameraleService();
        $res = $servece->impresa($request);

        if ($res) {
            session()->put('imprese', $res->data);
            return redirect()->action([VisuraCameraleController::class, 'showAziende']);

        } else {


            return redirect()->back();
        }


    }


    public function showAziende()
    {
        $imprese = session()->get('imprese');
        return view('Backend.VisuraCamerale.showAziende', [
            'record' => new ContrattoTelefonia(),
            'titoloPagina' => 'Risultati ricerca',
            'imprese' => $imprese,
            'breadcrumbs' => [action([VisuraCameraleController::class, 'showCercaAzienda']) => 'Nuova ricerca' ]

        ]);

    }


    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\OpenApiSmsService;
use App\Models\ClienteAssistenza;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sms;
use DB;
use function App\getInputTelefono;

class SmsController extends Controller
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
                'html' => base64_encode(view('Backend.Sms.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.Sms.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Sms::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\Sms::NOME_SINGOLARE,
            'testoCerca' => null

        ]);

        return view('Backend.Sms.index', [
            'records' => $this->queryBuilderIndex(),
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . \App\Models\Sms::NOME_PLURALE,
            'testoNuovo' => 'Nuovo ' . \App\Models\Sms::NOME_SINGOLARE,
            'testoCerca' => null
        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Sms::query();
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


    public function create()
    {
        return view('Backend.Sms.create', [
            'titoloPagina' => 'Invio sms',
            'record' => new User(),
            'controller' => SmsController::class,
            'contatti' => ClienteAssistenza::count(),
            'breadcrumbs' => [action([SmsController::class, 'index']) => 'Torna a elenco ' . Sms::NOME_PLURALE]
        ]);
    }


    public function store(Request $request)
    {


        if ($request->input('test')) {
            $telefoni = [];
            $telefono = getInputTelefono(Auth::user()->telefono);
            $telefoni[] = \Str::replace('+39', '+39-', $telefono);
        } else {
            $records = ClienteAssistenza::whereNotNull('telefono')->get();
            $telefoni = [];
            foreach ($records as $record) {
                $telefono = getInputTelefono($record->telefono);
                $telefoni[] = \Str::replace('+39', '+39-', $telefono);
            }
        }


        $service = new OpenApiSmsService();

        $sms = new Sms();
        $sms->testo = $request->input('testo');
        $sms->recipients = $telefoni;
        $sms->save();
        $res = $service->sendMessage($sms->testo, $telefoni);
        $sms->esito = $res->status();
        $sms->response = $res->json();
        if ($res->status() == 201) {
            $sms->credito = $res->json('credit');
        }
        $sms->save();


        return redirect()->action([SmsController::class, 'index']);
    }

    public function show($id)
    {
        $record = Sms::find($id);

        return view('Backend.Sms.show', [
            'titoloPagina' => 'Invio sms',
            'record' => $record,
            'controller' => SmsController::class,
            'breadcrumbs' => [action([SmsController::class, 'index']) => 'Torna a elenco ' . Sms::NOME_PLURALE]
        ]);
    }
}

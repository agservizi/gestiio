<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\PdfProformaCh;
use App\Http\Services\BrtService;
use App\Http\Services\BrtTariffaService;
use App\Http\Services\PortafoglioService;
use App\Models\Agente;
use App\Models\BorderoBrt;
use App\Models\ContattoBrt;
use App\Models\ListinoBrt;
use App\Models\ListinoBrtEuropa;
use App\Models\MovimentoPortafoglio;
use App\Models\NazioneEuropaBrt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SpedizioneBrt;
use DB;
use Illuminate\Support\Facades\Response;
use Faker\Generator;
use Illuminate\Container\Container;
use PDF;
use function App\getInputNumero;


class SpedizioneBrtController extends Controller
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
                'html' => base64_encode(view('Backend.SpedizioneBrt.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.SpedizioneBrt.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\SpedizioneBrt::NOME_PLURALE,
            'orderBy' => null,//$orderBy,
            'ordinamenti' => null,// $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\SpedizioneBrt::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in destinatario, mittente'

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\SpedizioneBrt::query()
            ->latest()
            ->with('agente:id,alias');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',ragione_sociale_destinatario,nome_mittente)'), 'like', "%$t%");
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
    public function create($zona = null)
    {

        if (!$zona) {
            return view('Backend.SpedizioneBrt.create', [
                'titoloPagina' => 'Nuova spedizione',
                'controller' => get_class($this),
            ]);
        }
        $record = new SpedizioneBrt();
        if ($zona === 'ITALIA') {
            $record->nazione_destinazione = 'IT';
        }
        if (Auth::user()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }
        return view('Backend.SpedizioneBrt.edit', [
            'zona' => $zona,
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . SpedizioneBrt::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([SpedizioneBrtController::class, 'index']) => 'Torna a elenco ' . SpedizioneBrt::NOME_PLURALE]

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
        $record = new SpedizioneBrt();
        $this->salvaDati($record, $request);


        if ($request->input('salva_anagrafica')) {
            $contatto = new ContattoBrt();
            $contatto->agente_id = $record->agente_id;
            $contatto->ragione_sociale_destinatario = $record->ragione_sociale_destinatario;
            $contatto->indirizzo_destinatario = $record->indirizzo_destinatario;
            $contatto->cap_destinatario = $record->cap_destinatario;
            $contatto->localita_destinazione = $record->localita_destinazione;
            $contatto->provincia_destinatario = $record->provincia_destinatario;
            $contatto->nazione_destinazione = $record->nazione_destinazione;
            $contatto->save();
        }

        $brtService = new BrtService();
        $res = $brtService->shipment($record);

        $record->response = $res;
        if ($res['createResponse']['labels'] ?? false) {
            $record->labels = $res['createResponse']['labels'];
        }
        if ($record->response['createResponse'] ?? false) {
            $esito = \Arr::get($record->response['createResponse'], 'executionMessage');
            if ($esito) {
                $record->esito = \Arr::get($esito, 'severity');
                $message = \Arr::get($esito, 'message');
                if ($message) {
                    $record->esito_testo = \Arr::get($esito, 'code') . ' ' . $message;
                }
            }
            $record->save();
        }


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
        $record = SpedizioneBrt::with(['chiamate' => function ($q) {
            $q->latest();
        }])->find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');
        return view('Backend.SpedizioneBrt.show', [
            'record' => $record,
            'controller' => SpedizioneBrtController::class,
            'titoloPagina' => SpedizioneBrt::NOME_SINGOLARE,
            'breadcrumbs' => [action([SpedizioneBrtController::class, 'index']) => 'Torna a elenco ' . SpedizioneBrt::NOME_PLURALE]

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
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        if ($record->nazione_destinazione == 'IT') {
            $zona = 'ITALIA';
        } else {
            $zona = 'EUROPA';
        }
        return view('Backend.SpedizioneBrt.edit', [
            'zona' => $zona,
            'record' => $record,
            'controller' => SpedizioneBrtController::class,
            'titoloPagina' => 'Modifica ' . SpedizioneBrt::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([SpedizioneBrtController::class, 'index']) => 'Torna a elenco ' . SpedizioneBrt::NOME_PLURALE]

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
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa ' . SpedizioneBrt::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        $brtService = new BrtService();
        $res = $brtService->shipment($record);

        $record->response = $res;
        if ($res['createResponse']['labels'] ?? false) {
            $record->labels = $res['createResponse']['labels'];
        }
        if ($record->response['createResponse'] ?? false) {
            $esito = \Arr::get($record->response['createResponse'], 'executionMessage');
            if ($esito) {
                $record->esito = \Arr::get($esito, 'severity');
                $message = \Arr::get($esito, 'message');
                if ($message) {
                    $record->esito_testo = \Arr::get($esito, 'code') . ' ' . $message;
                }
            }
            $record->save();
        }
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
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([SpedizioneBrtController::class, 'index']),
        ];
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function annulla($id)
    {
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');



        $service = new BrtService();
        $res = $service->delete($record);


        if ($res['deleteResponse'] ?? false) {
            $esito = \Arr::get($res['deleteResponse'], 'executionMessage');
            if ($esito) {
                if (\Arr::get($esito, 'codeDesc') == 'SHIPMENT DELETED') {
                    $record->esito = 'ANNULLATA';
                    $record->save();

                    $portafoglioService = new PortafoglioService();
                    $portafoglioService->annullaMovimento($record->id, SpedizioneBrt::class);

                }
            }
        }


        return [
            'success' => true,
            'redirect' => action([SpedizioneBrtController::class, 'index']),
        ];
    }

    public function etichetta($id, $index)
    {
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');
        $datiEtichetta = $record->response['createResponse']['labels']['label'][$index]['stream'];

        return Response::make(base64_decode($datiEtichetta), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . 'filename.pdf' . '"',
        ]);

        return response(base64_decode($datiEtichetta))->header('Content-Disposition', 'attachment; filename="Etichetta.pdf"');
    }


    public function pdf($id, $tipopdf)
    {
        $record = SpedizioneBrt::find($id);
        abort_if(!$record, 404, 'Questa spedizione brt non esiste');
        switch ($tipopdf) {
            case 'proforma_ch':
                $pdf = new PdfProformaCh();
                $pdf->generaPdf($record);
                return $pdf->render();
        }
    }


    public function bordero(Request $request, $id = null)
    {
        $recordsQb = $this->applicaFiltri($request);

        if ($id) {
            $recordsQb->where('bordero_id', $id);
            $bordero = BorderoBrt::find($id);

            $pdf = PDF::loadView('Backend.SpedizioneBrt.borderoPdf', [
                'records' => $recordsQb->get(),
                'bordero' => $bordero
            ]);


            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('Borderò.pdf');

        } else {
            $recordsQb->whereIn('id', explode(',', $request->input('id')))->whereNull('bordero_id');

            $records = $recordsQb->get();
            if ($records->count()) {
                $bordero = new BorderoBrt();
                $bordero->save();
                foreach ($records as $record) {
                    $record->bordero_id = $bordero->id;
                    $record->save();
                }
                $pdf = PDF::loadView('Backend.SpedizioneBrt.borderoPdf', [
                    'records' => $records,
                    'bordero' => $bordero
                ]);


                $pdf->setPaper('A4', 'landscape');

                return $pdf->stream('Borderò.pdf');
            }
        }


        abort(404);

    }


    public function showPrezziAgenti()
    {

        $queryBuilder = User::where('id', '>', 1)
            ->whereHas('permissions', function ($q) {
                $q->where('name', 'servizio_spedizioni');
            })
            ->with('agente');

        return view('Backend.SpedizioneBrt.indexPrezziAgenti', [
            'records' => $queryBuilder->get(),
            'controller' => SpedizioneBrtController::class,
            'titoloPagina' => 'Elenco agenti abilitati spedizioni',
            'orderBy' => null,//$orderBy,
            'ordinamenti' => null,// $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoCerca' => 'Cerca in destinatario, mittente'
        ]);
    }

    public function updateRicaricoAgenti(Request $request)
    {
        $users = User::where('id', '>', 1)
            ->whereHas('permissions', function ($q) {
                $q->where('name', 'servizio_spedizioni');
            })
            ->with('agente')->get();

        foreach ($users as $user) {
            $agente = $user->agente;
            $agente->variazione_prezzi_spedizioni = getInputNumero($request->input('prezzo' . $user->id));
            $agente->save();
        }

        return redirect()->action([SpedizioneBrtController::class, 'showPrezziAgenti']);
    }

    /**
     * @param SpedizioneBrt $record
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($record, $request)
    {

        DB::beginTransaction();
        $nuovo = !$record->id;

        if ($nuovo) {
            $record->tipo_porto = 'DAP';
            $record->caricato_da_user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'agente_id' => '',
            'ragione_sociale_destinatario' => '',
            'indirizzo_destinatario' => '',
            'cap_destinatario' => '',
            'localita_destinazione' => '',
            'nazione_destinazione' => '',
            'mobile_referente_consegna' => '',
            'numero_pacchi' => '',
            'peso_totale' => 'app\getInputNumero',
            'volume_totale' => 'app\getInputNumero',
            'pudo_id' => '',
            'nome_mittente' => '',
            'email_mittente' => '',
            'mobile_mittente' => '',
            'provincia_destinatario' => '',
            'contrassegno' => 'app\getInputNumero',
            'cap_mittente' => '',
            'dati_colli' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $record->$campo = $valore;
        }

        $nazione = $record->nazione_destinazione;
        $pesoTotale = $record->peso_totale;
        $pudo = $record->pudo_id;
        $colli = $request->input('numero_pacchi');
        $contrassegno = $record->contrassegno;
        if ($request->input('nazione_destinazione') == 'CH') {
            $record->altri_dati = $request->input('altri_dati');
        }

        $service = new BrtTariffaService($nazione, $pesoTotale, $colli, $pudo, $contrassegno);
        $service->calcola();
        if (!$service->isError()) {
            $record->prezzo_spedizione = $service->getPrezzo();
            $record->tariffa = $service->getTestotariffa();
        }


        if (!$record->scalato_portafoglio && Auth::user()->agente->portafoglio_spedizioni < $record->prezzo_spedizione) {
            return redirect()->back()->withErrors(['portafoglio' => 'Credito portafoglio insufficiente']);
        }
        $record->save();

        if (!$service->isError() && !$record->scalato_portafoglio) {


            $movimento = new MovimentoPortafoglio();
            $movimento->agente_id = Auth::id();
            $movimento->importo = -$record->prezzo_spedizione;
            $movimento->descrizione = 'Spedizione Brt ' . $record->id;
            $movimento->prodotto_id = $record->id;
            $movimento->prodotto_type = get_class($record);
            $movimento->portafoglio = TipiPortafoglioEnum::SPEDIZIONI->value;
            $movimento->save();
            $record->scalato_portafoglio = 1;
            $record->saveQuietly();

        }

        DB::commit();

        return $record;
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
        return \App\Models\SpedizioneBrt::get();
    }


    protected function rules($id = null)
    {

        $rules = [
            'ragione_sociale_destinatario' => ['required', 'max:70'],
            'indirizzo_destinatario' => ['required', 'max:35'],
            'cap_destinatario' => ['required', 'max:9'],
            'localita_destinazione' => ['required', 'max:35'],
            'nazione_destinazione' => ['required'],
            'mobile_referente_consegna' => ['required', 'max:16'],
            'numero_pacchi' => ['required'],
            'peso_totale' => ['required'],
            'volume_totale' => ['required'],
            'pudo_id' => ['nullable', 'max:255'],
            'nome_mittente' => ['required', 'max:25'],
            'email_mittente' => ['nullable', 'max:255'],
            'mobile_mittente' => ['nullable', 'max:255'],
            'dati_colli.*.larghezza'=>['required'],
            'dati_colli.*.altezza'=>['required'],
            'dati_colli.*.profondita'=>['required'],
        ];

        return $rules;
    }

}

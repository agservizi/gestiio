<?php

namespace App\Http\Controllers\Backend;

use App\Enums\FatturaProformaStatus;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Http\Requests\UpdateFatturaProformaIntestazioneRequest;
use App\Http\Services\FatturaProformaService;
use App\Models\FatturaProforma;
use App\Notifications\NotificaFatturaProforma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PDF;

class FatturaProformaController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', FatturaProforma::class);

        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],
            'numero' => ['testo' => 'Numero', 'filtro' => function ($q) {
                return $q->orderByDesc('numero');
            }],
            'totale' => ['testo' => 'Totale', 'filtro' => function ($q) {
                return $q->orderByDesc('totale_con_iva');
            }],
            'nominativo' => ['testo' => 'Intestazione', 'filtro' => function ($q) {
                return $q->join('fatture_proforma_intestazioni', 'fatture_proforma_intestazioni.id', '=', 'fatture_proforma.intestazione_id')
                    ->orderBy('fatture_proforma_intestazioni.denominazione')
                    ->select('fatture_proforma.*');
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

        if (! isset($ordinamenti[$orderBy])) {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }

        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.FatturaProforma.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])),
            ];
        }

        return view('Backend.FatturaProforma.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.FatturaProforma::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $request->input('filtro', 'tutti'),
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null,
            'testoCerca' => 'Cerca intestazione o numero',
            'filtri' => [
                'status' => $request->input('status'),
                'anno' => $request->input('anno'),
            ],
            'stati' => FatturaProformaStatus::cases(),
        ]);
    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {
        $queryBuilder = FatturaProforma::query()
            ->with(['intestazione:id,denominazione,user_id', 'produzione:id,fattura_proforma_id,anno,mese']);

        $term = $request->input('cerca');
        if ($term) {
            $this->conFiltro = true;
            $queryBuilder->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->where('numero', (int) $term);
                }
                $q->orWhereHas('intestazione', function ($iq) use ($term) {
                    $iq->where('denominazione', 'like', "%{$term}%");
                });
            });
        }

        if ($request->filled('status')) {
            $this->conFiltro = true;
            $queryBuilder->where('status', $request->input('status'));
        }

        if ($request->filled('anno')) {
            $this->conFiltro = true;
            $queryBuilder->whereYear('data', (int) $request->input('anno'));
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
        $record = FatturaProforma::with(['intestazione', 'righe', 'produzione'])->find($id);
        abort_if(! $record, 404, 'Questa fattura proforma non esiste');
        $this->authorize('view', $record);

        $service = new FatturaProformaService(
            optional($record->produzione)->anno ?? $record->data->year,
            optional($record->produzione)->mese ?? $record->data->month
        );

        return view('Backend.FatturaProforma.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => ucfirst(FatturaProforma::NOME_SINGOLARE).' #'.$record->numero,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.FatturaProforma::NOME_PLURALE],
            'intestazioneIncompleta' => $service->isIntestazioneIncompleta($record->intestazione),
        ]);
    }

    public function pdf($id)
    {
        $record = FatturaProforma::with(['intestazione', 'righe'])->find($id);
        abort_if(! $record, 404, 'Questa fattura proforma non esiste');
        $this->authorize('view', $record);

        $pdf = PDF::loadView('Backend.FatturaProforma.pdf', [
            'record' => $record,
        ]);

        $filename = $this->pdfFilename($record);

        return $pdf->stream($filename);
    }

    public function updateIntestazione(UpdateFatturaProformaIntestazioneRequest $request, $id)
    {
        $record = FatturaProforma::with('intestazione')->find($id);
        abort_if(! $record, 404, 'Questa fattura proforma non esiste');
        $this->authorize('updateIntestazione', $record);

        $intestazione = $record->intestazione;
        abort_if(! $intestazione, 404, 'Intestazione non trovata');

        foreach (['denominazione', 'codice_fiscale', 'indirizzo', 'citta', 'cap', 'nazione'] as $campo) {
            if ($request->has($campo)) {
                $intestazione->$campo = $request->input($campo);
            }
        }
        $intestazione->save();

        $alert = new AlertMessage;
        $alert->messaggio('Intestazione aggiornata', 'success')->flash();

        return redirect()->action([self::class, 'show'], $record->id);
    }

    public function emetti(Request $request, $id)
    {
        $record = FatturaProforma::find($id);
        abort_if(! $record, 404);
        $this->authorize('emit', $record);

        $record->status = FatturaProformaStatus::EMESSA;
        $record->save();

        $alert = new AlertMessage;
        $alert->messaggio('Proforma #'.$record->numero.' emessa', 'success')->flash();

        return redirect()->action([self::class, 'show'], $record->id);
    }

    public function inviaEmail(Request $request, $id)
    {
        $record = FatturaProforma::with(['intestazione.agente', 'righe'])->find($id);
        abort_if(! $record, 404);
        $this->authorize('sendEmail', $record);

        $agente = optional($record->intestazione)->agente;
        if (! $agente || ! $agente->email) {
            $alert = new AlertMessage;
            $alert->messaggio('Email agente non disponibile', 'danger')->flash();

            return redirect()->action([self::class, 'show'], $record->id);
        }

        $pdf = PDF::loadView('Backend.FatturaProforma.pdf', ['record' => $record]);
        $filename = $this->pdfFilename($record);

        Notification::send($agente, new NotificaFatturaProforma($record, $pdf->output(), $filename));

        if ($record->statusEnum() !== FatturaProformaStatus::PAGATA) {
            $record->status = FatturaProformaStatus::INVIATA;
            $record->save();
        }

        $alert = new AlertMessage;
        $alert->messaggio('Email inviata a '.$agente->email, 'success')->flash();

        return redirect()->action([self::class, 'show'], $record->id);
    }

    public function segnaPagata(Request $request, $id)
    {
        $record = FatturaProforma::find($id);
        abort_if(! $record, 404);
        $this->authorize('markPaid', $record);

        $record->status = FatturaProformaStatus::PAGATA;
        $record->save();

        $alert = new AlertMessage;
        $alert->messaggio('Proforma #'.$record->numero.' segnata come pagata', 'success')->flash();

        return redirect()->action([self::class, 'show'], $record->id);
    }

    public function rigenera(Request $request, $id)
    {
        $record = FatturaProforma::with('produzione')->find($id);
        abort_if(! $record, 404);
        $this->authorize('regenerate', $record);

        $anno = optional($record->produzione)->anno ?? $record->data->year;
        $mese = optional($record->produzione)->mese ?? $record->data->month;
        $service = new FatturaProformaService($anno, $mese);

        $alert = new AlertMessage;
        if (! $service->rigenera($record)) {
            $alert->messaggio($service->getErrore() ?: 'Rigenerazione non riuscita', 'danger')->flash();
        } else {
            $alert->messaggio('Proforma rigenerata dalle produzioni', 'success')->flash();
        }

        return redirect()->action([self::class, 'show'], $record->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $record = FatturaProforma::find($id);
        abort_if(! $record, 404, 'Questa fattura proforma non esiste');
        $this->authorize('delete', $record);

        $service = new FatturaProformaService(
            optional($record->produzione)->anno ?? now()->year,
            optional($record->produzione)->mese ?? now()->month
        );

        if (! $service->elimina($record)) {
            return response()->json([
                'success' => false,
                'message' => $service->getErrore() ?: 'Eliminazione non consentita',
            ], 422);
        }

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    protected function pdfFilename(FatturaProforma $record): string
    {
        $anno = optional($record->data)->format('Y') ?? date('Y');

        return 'proforma-'.$record->numero.'-'.$anno.'.pdf';
    }
}

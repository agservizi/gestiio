<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\Billing\FatturaPaExportService;
use App\Http\Services\Billing\FornitoreSettlementService;
use App\Http\Services\InvoiceShelf\InvoiceShelfClient;
use App\Models\BillingDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingDocumentController extends Controller
{
    public function __construct(
        protected FornitoreSettlementService $settlement,
        protected InvoiceShelfClient $client,
        protected FatturaPaExportService $fatturaPa
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingDocument::class);

        $query = BillingDocument::query()->orderByDesc('id');
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $records = $query->paginate(30)->withQueryString();

        return view('Backend.Billing.index', [
            'controller' => self::class,
            'titoloPagina' => 'Fatturazione',
            'records' => $records,
            'filtri' => $request->only(['source', 'status']),
            'invoiceshelfEnabled' => $this->client->enabled(),
        ]);
    }

    public function invoiceshelfIndex(Request $request)
    {
        $this->authorize('viewAny', BillingDocument::class);

        if (! $this->client->enabled()) {
            return view('Backend.Billing.invoiceshelf', [
                'controller' => self::class,
                'titoloPagina' => 'Fatture InvoiceShelf',
                'invoices' => [],
                'error' => 'InvoiceShelf non configurato.',
                'breadcrumbs' => [
                    action([self::class, 'index']) => 'Fatturazione',
                ],
            ]);
        }

        $page = max(1, (int) $request->input('page', 1));
        $error = null;
        $invoices = [];
        try {
            $response = $this->client->listInvoices([
                'orderByField' => 'created_at',
                'orderBy' => 'desc',
                'limit' => 30,
                'page' => $page,
            ]);
            $invoices = $response['data'] ?? [];
            if (! is_array($invoices)) {
                $invoices = [];
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('Backend.Billing.invoiceshelf', [
            'controller' => self::class,
            'titoloPagina' => 'Fatture InvoiceShelf',
            'invoices' => $invoices,
            'error' => $error,
            'page' => $page,
            'breadcrumbs' => [
                action([self::class, 'index']) => 'Fatturazione',
            ],
        ]);
    }

    public function exportXml($id): Response
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('view', $record);

        try {
            $result = $this->fatturaPa->exportBillingDocument($record);
        } catch (\Throwable $e) {
            \Log::warning('Export FatturaPA fallito', [
                'billing_document_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->action([self::class, 'show'], $record->id)
                ->with('error', 'Export XML non riuscito: '.$e->getMessage());
        }

        return response($result['xml'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    public function exportInvoiceShelfXml($isId): Response
    {
        $this->authorize('viewAny', BillingDocument::class);

        try {
            $result = $this->fatturaPa->exportInvoiceShelfId((int) $isId);
        } catch (\Throwable $e) {
            \Log::warning('Export FatturaPA IS fallito', [
                'invoiceshelf_id' => $isId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->action([self::class, 'invoiceshelfIndex'])
                ->with('error', 'Export XML non riuscito: '.$e->getMessage());
        }

        return response($result['xml'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    public function show($id)
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('view', $record);

        $remote = null;
        if ($this->client->enabled() && $record->invoiceshelf_id) {
            try {
                $remote = $record->invoiceshelf_type === 'invoice'
                    ? $this->client->getInvoice($record->invoiceshelf_id)
                    : $this->client->getEstimate($record->invoiceshelf_id);
            } catch (\Throwable $e) {
                $remote = ['error' => $e->getMessage()];
            }
        }

        return view('Backend.Billing.show', [
            'controller' => self::class,
            'titoloPagina' => $record->labelSource().' '.$record->periodo,
            'record' => $record,
            'remote' => $remote,
            'breadcrumbs' => [
                action([self::class, 'index']) => 'Fatturazione',
            ],
        ]);
    }

    public function pdf($id): Response
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('view', $record);

        if ($this->client->enabled() && $record->unique_hash) {
            $body = $this->client->downloadPdfByHash(
                $record->invoiceshelf_type === 'invoice' ? 'invoice' : 'estimate',
                $record->unique_hash
            );

            return response($body, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="proforma-'.$record->id.'.pdf"',
            ]);
        }

        // Offline fallback: HTML printable summary
        $html = view('Backend.Billing.pdf_offline', ['record' => $record])->render();

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function emetti($id)
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('update', $record);

        try {
            $this->settlement->emetti($record);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Documento emesso.');
    }

    public function segnaPagata($id)
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('update', $record);

        if (! in_array($record->status, ['emessa', 'inviata', 'bozza'], true)) {
            return back()->with('error', 'Stato non valido per segnare come pagata.');
        }

        $this->settlement->segnaPagata($record);

        return back()->with('success', 'Documento segnato come pagato.');
    }

    public function convertToInvoice($id)
    {
        $record = BillingDocument::findOrFail($id);
        $this->authorize('update', $record);

        if (! $this->client->enabled() || ! $record->invoiceshelf_id || $record->invoiceshelf_type !== 'estimate') {
            return back()->with('error', 'Conversione disponibile solo per estimate InvoiceShelf.');
        }

        try {
            $response = $this->client->convertEstimateToInvoice($record->invoiceshelf_id);
            $data = $response['data'] ?? $response;
            $record->invoiceshelf_type = 'invoice';
            $record->invoiceshelf_id = (int) ($data['id'] ?? $record->invoiceshelf_id);
            $record->unique_hash = $data['unique_hash'] ?? $record->unique_hash;
            $record->status = $record->status === 'bozza' ? 'emessa' : $record->status;
            $meta = $record->meta ?? [];
            $meta['converted_at'] = now()->toIso8601String();
            $record->meta = $meta;
            $record->save();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Proforma convertita in fattura su InvoiceShelf.');
    }
}

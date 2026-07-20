<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\Billing\FornitoreSettlementService;
use App\Http\Services\InvoiceShelf\InvoiceShelfClient;
use App\Models\BillingDocument;
use Illuminate\Http\Request;

class BillingFornitoreController extends Controller
{
    public function __construct(
        protected FornitoreSettlementService $settlement,
        protected InvoiceShelfClient $client
    ) {
    }

    public function caf(Request $request)
    {
        $this->authorize('viewAny', BillingDocument::class);

        return $this->hub($request, BillingDocument::SOURCE_CAF_MONTHLY, 'Proforma CAF/Patronato');
    }

    public function send(Request $request)
    {
        $this->authorize('viewAny', BillingDocument::class);

        return $this->hub($request, BillingDocument::SOURCE_SEND_MONTHLY, 'Proforma SEND');
    }

    public function previewCaf(Request $request)
    {
        $this->authorize('create', BillingDocument::class);
        [$anno, $mese] = $this->resolvePeriod($request);
        $preview = $this->settlement->previewCaf($anno, $mese);

        return view('Backend.Billing.preview', [
            'controller' => self::class,
            'titoloPagina' => 'Anteprima proforma CAF/Patronato',
            'source' => BillingDocument::SOURCE_CAF_MONTHLY,
            'anno' => $anno,
            'mese' => $mese,
            'preview' => $preview,
            'generateAction' => action([self::class, 'generaCaf']),
            'backAction' => action([self::class, 'caf'], ['anno' => $anno, 'mese' => $mese]),
        ]);
    }

    public function previewSend(Request $request)
    {
        $this->authorize('create', BillingDocument::class);
        [$anno, $mese] = $this->resolvePeriod($request);
        $preview = $this->settlement->previewSend($anno, $mese);

        return view('Backend.Billing.preview', [
            'controller' => self::class,
            'titoloPagina' => 'Anteprima proforma SEND',
            'source' => BillingDocument::SOURCE_SEND_MONTHLY,
            'anno' => $anno,
            'mese' => $mese,
            'preview' => $preview,
            'generateAction' => action([self::class, 'generaSend']),
            'backAction' => action([self::class, 'send'], ['anno' => $anno, 'mese' => $mese]),
        ]);
    }

    public function generaCaf(Request $request)
    {
        $this->authorize('create', BillingDocument::class);
        [$anno, $mese] = $this->resolvePeriod($request);
        $force = $request->boolean('force');

        try {
            $doc = $this->settlement->generaProformaCaf($anno, $mese, $force);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->action([BillingDocumentController::class, 'show'], $doc->id)
            ->with('success', 'Proforma CAF/Patronato generata.');
    }

    public function generaSend(Request $request)
    {
        $this->authorize('create', BillingDocument::class);
        [$anno, $mese] = $this->resolvePeriod($request);
        $force = $request->boolean('force');

        try {
            $doc = $this->settlement->generaProformaSend($anno, $mese, $force);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->action([BillingDocumentController::class, 'show'], $doc->id)
            ->with('success', 'Proforma SEND generata.');
    }

    protected function hub(Request $request, string $source, string $titolo)
    {
        [$anno, $mese] = $this->resolvePeriod($request);
        $periodo = $this->settlement->periodoKey($anno, $mese);
        $existing = BillingDocument::query()
            ->where('source', $source)
            ->where('periodo', $periodo)
            ->first();

        $preview = $source === BillingDocument::SOURCE_CAF_MONTHLY
            ? $this->settlement->previewCaf($anno, $mese)
            : $this->settlement->previewSend($anno, $mese);

        $history = BillingDocument::query()
            ->where('source', $source)
            ->orderByDesc('periodo')
            ->limit(24)
            ->get();

        return view('Backend.Billing.hub', [
            'controller' => self::class,
            'titoloPagina' => $titolo,
            'source' => $source,
            'anno' => $anno,
            'mese' => $mese,
            'periodo' => $periodo,
            'existing' => $existing,
            'preview' => $preview,
            'history' => $history,
            'invoiceshelfEnabled' => $this->client->enabled(),
            'previewAction' => $source === BillingDocument::SOURCE_CAF_MONTHLY
                ? action([self::class, 'previewCaf'])
                : action([self::class, 'previewSend']),
            'generateAction' => $source === BillingDocument::SOURCE_CAF_MONTHLY
                ? action([self::class, 'generaCaf'])
                : action([self::class, 'generaSend']),
        ]);
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function resolvePeriod(Request $request): array
    {
        $anno = (int) ($request->input('anno') ?: now()->subMonth()->year);
        $mese = (int) ($request->input('mese') ?: now()->subMonth()->month);

        return [$anno, max(1, min(12, $mese))];
    }
}

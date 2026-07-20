<?php

namespace App\Http\Services\Billing;

use App\Enums\SendRequestStatus;
use App\Http\Services\InvoiceShelf\InvoiceShelfClient;
use App\Models\BillingDocument;
use App\Models\CafPatronato;
use App\Models\EsitoCafPatronato;
use App\Models\SendRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class FornitoreSettlementService
{
    public function __construct(protected InvoiceShelfClient $client)
    {
    }

    public function periodoKey(int $anno, int $mese): string
    {
        return sprintf('%04d_%02d', $anno, $mese);
    }

    /**
     * @return array{ok:bool,error?:string,periodo:string,label:string,rows:Collection,totale:float,count:int,excluded_count:int}
     */
    public function previewCaf(int $anno, int $mese): array
    {
        return $this->preview(
            BillingDocument::SOURCE_CAF_MONTHLY,
            $anno,
            $mese,
            $this->eligibleCafRows($anno, $mese),
            $this->excludedCafCount($anno, $mese)
        );
    }

    /**
     * @return array{ok:bool,error?:string,periodo:string,label:string,rows:Collection,totale:float,count:int,excluded_count:int}
     */
    public function previewSend(int $anno, int $mese): array
    {
        return $this->preview(
            BillingDocument::SOURCE_SEND_MONTHLY,
            $anno,
            $mese,
            $this->eligibleSendRows($anno, $mese),
            $this->excludedSendCount($anno, $mese)
        );
    }

    public function generaProformaCaf(int $anno, int $mese, bool $force = false): BillingDocument
    {
        return $this->genera(BillingDocument::SOURCE_CAF_MONTHLY, $anno, $mese, $force, function () use ($anno, $mese) {
            return $this->previewCaf($anno, $mese);
        }, function (Collection $rows) {
            return $rows->map(fn ($r) => [
                'name' => 'Pratica CAF/Patronato #'.$r->id.' — '.($r->nome ?? ''),
                'description' => 'Tipo ID '.$r->tipo_caf_patronato_id,
                'quantity' => 1,
                'price' => (float) $r->importo_fornitore,
            ])->all();
        });
    }

    public function generaProformaSend(int $anno, int $mese, bool $force = false): BillingDocument
    {
        return $this->genera(BillingDocument::SOURCE_SEND_MONTHLY, $anno, $mese, $force, function () use ($anno, $mese) {
            return $this->previewSend($anno, $mese);
        }, function (Collection $rows) {
            return $rows->map(fn ($r) => [
                'name' => 'Pratica SEND '.$r->request_number,
                'description' => 'Status '.$r->status?->value,
                'quantity' => 1,
                'price' => (float) $r->importo_fornitore,
            ])->all();
        });
    }

    public function segnaPagata(BillingDocument $doc): BillingDocument
    {
        if ($doc->isPaid()) {
            return $doc;
        }

        if ($this->client->enabled() && $doc->invoiceshelf_id && $doc->invoiceshelf_type === 'invoice') {
            try {
                $this->client->createPayment([
                    'payment_date' => now()->toDateString(),
                    'payment_number' => 'PAY-GESTIIO-'.$doc->id.'-'.now()->format('YmdHis'),
                    'customer_id' => $this->client->ensureFornitoreCustomer(),
                    'invoice_id' => $doc->invoiceshelf_id,
                    'amount' => (float) $doc->totale,
                    'payment_method_id' => 1,
                ]);
            } catch (\Throwable $e) {
                // Local status still advances; payment on IS may need manual fix
                report($e);
            }
        }

        $doc->status = 'pagata';
        $meta = $doc->meta ?? [];
        $meta['paid_at'] = now()->toIso8601String();
        $doc->meta = $meta;
        $doc->save();

        return $doc;
    }

    public function emetti(BillingDocument $doc): BillingDocument
    {
        if ($doc->status !== 'bozza') {
            throw new InvalidArgumentException('Solo documenti in bozza possono essere emessi.');
        }
        $doc->status = 'emessa';
        $doc->save();

        return $doc;
    }

    /**
     * @param  callable(): array  $previewFn
     * @param  callable(Collection): array  $itemsFn
     */
    protected function genera(string $source, int $anno, int $mese, bool $force, callable $previewFn, callable $itemsFn): BillingDocument
    {
        $periodo = $this->periodoKey($anno, $mese);
        $idempotencyKey = $source.':'.$periodo;
        $existing = BillingDocument::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing && ! $force) {
            throw new InvalidArgumentException('Proforma già esistente per questo periodo. Usa rigenera.');
        }
        if ($existing && $existing->isPaid()) {
            throw new InvalidArgumentException('Proforma già pagata: non rigenerabile.');
        }

        $preview = $previewFn();
        if (! ($preview['ok'] ?? false)) {
            throw new InvalidArgumentException($preview['error'] ?? 'Nessun importo da fatturare.');
        }

        $items = $itemsFn($preview['rows']);
        $isPayload = null;
        $isId = null;
        $hash = null;
        $isType = 'estimate';

        if ($this->client->enabled()) {
            $customerId = $this->client->ensureFornitoreCustomer();
            $estimateDate = Carbon::createFromDate($anno, $mese, 1)->endOfMonth()->toDateString();
            $response = $this->client->createEstimate([
                'estimate_date' => $estimateDate,
                'expiry_date' => Carbon::parse($estimateDate)->addDays(30)->toDateString(),
                'customer_id' => $customerId,
                'status' => 'DRAFT',
                'template_name' => config('invoiceshelf.estimate_template', 'estimate1'),
                'notes' => $preview['label'].' — '.$source,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_val' => 0,
                'sub_total' => $preview['totale'],
                'total' => $preview['totale'],
                'tax' => 0,
                'items' => collect($items)->map(function (array $item, int $i) {
                    return [
                        'name' => $item['name'],
                        'description' => $item['description'] ?? '',
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'total' => $item['quantity'] * $item['price'],
                        'tax' => 0,
                        'taxes' => [],
                    ];
                })->values()->all(),
            ]);

            $data = $response['data'] ?? $response;
            $isId = (int) ($data['id'] ?? 0);
            $hash = $data['unique_hash'] ?? null;
            $isPayload = ['estimate' => $data];
            if ($isId <= 0) {
                throw new RuntimeException('InvoiceShelf non ha restituito un ID estimate.');
            }
        }

        return DB::transaction(function () use ($existing, $source, $periodo, $idempotencyKey, $preview, $items, $isId, $hash, $isType, $isPayload) {
            $doc = $existing ?: new BillingDocument;
            $doc->source = $source;
            $doc->periodo = $periodo;
            $doc->idempotency_key = $idempotencyKey;
            $doc->status = 'bozza';
            $doc->invoiceshelf_type = $isType;
            $doc->invoiceshelf_id = $isId;
            $doc->unique_hash = $hash;
            $doc->totale = $preview['totale'];
            $doc->meta = [
                'label' => $preview['label'],
                'count' => $preview['count'],
                'excluded_count' => $preview['excluded_count'],
                'items' => $items,
                'invoiceshelf' => $isPayload,
                'generated_at' => now()->toIso8601String(),
                'offline' => ! $this->client->enabled(),
            ];
            $doc->save();

            return $doc;
        });
    }

    /**
     * @return array{ok:bool,error?:string,periodo:string,label:string,rows:Collection,totale:float,count:int,excluded_count:int}
     */
    protected function preview(string $source, int $anno, int $mese, Collection $rows, int $excludedCount): array
    {
        $periodo = $this->periodoKey($anno, $mese);
        $label = Carbon::createFromDate($anno, $mese, 1)->translatedFormat('F Y');
        $totale = (float) $rows->sum(fn ($r) => (float) ($r->importo_fornitore ?? 0));

        if ($rows->isEmpty() || $totale <= 0) {
            return [
                'ok' => false,
                'error' => 'Nessuna pratica eligible con importo fornitore > 0 per '.$label,
                'periodo' => $periodo,
                'label' => $label,
                'rows' => $rows,
                'totale' => 0.0,
                'count' => 0,
                'excluded_count' => $excludedCount,
            ];
        }

        return [
            'ok' => true,
            'periodo' => $periodo,
            'label' => $label,
            'rows' => $rows,
            'totale' => $totale,
            'count' => $rows->count(),
            'excluded_count' => $excludedCount,
        ];
    }

    protected function eligibleCafRows(int $anno, int $mese): Collection
    {
        $from = Carbon::createFromDate($anno, $mese, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();

        $rimborsoIds = EsitoCafPatronato::query()
            ->where('nome', 'like', '%rimborso%')
            ->pluck('id')
            ->all();

        return CafPatronato::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('importo_fornitore', '>', 0)
            ->when(! empty($rimborsoIds), fn ($q) => $q->whereNotIn('esito_id', $rimborsoIds))
            ->where(function ($q) {
                $q->whereNull('esito_id')
                    ->orWhereNotIn('esito_id', ['bozza']);
            })
            ->orderBy('id')
            ->get();
    }

    protected function excludedCafCount(int $anno, int $mese): int
    {
        $from = Carbon::createFromDate($anno, $mese, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();
        $total = CafPatronato::query()->whereBetween('created_at', [$from, $to])->count();

        return max(0, $total - $this->eligibleCafRows($anno, $mese)->count());
    }

    protected function eligibleSendRows(int $anno, int $mese): Collection
    {
        $from = Carbon::createFromDate($anno, $mese, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();

        return SendRequest::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('importo_fornitore', '>', 0)
            ->whereIn('status', [
                SendRequestStatus::COMPLETED->value,
                SendRequestStatus::DELIVERED->value,
                SendRequestStatus::CLOSED->value,
            ])
            ->orderBy('id')
            ->get();
    }

    protected function excludedSendCount(int $anno, int $mese): int
    {
        $from = Carbon::createFromDate($anno, $mese, 1)->startOfMonth();
        $to = (clone $from)->endOfMonth();
        $total = SendRequest::query()->whereBetween('created_at', [$from, $to])->count();

        return max(0, $total - $this->eligibleSendRows($anno, $mese)->count());
    }
}

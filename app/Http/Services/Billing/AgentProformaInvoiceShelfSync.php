<?php

namespace App\Http\Services\Billing;

use App\Http\Services\InvoiceShelf\InvoiceShelfClient;
use App\Models\BillingDocument;
use App\Models\FatturaProforma;
use App\Models\IntestazioneFatturaProforma;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Sync local agent proforma → InvoiceShelf estimate + billing_documents mapping.
 * Local DomPDF remains available; IS becomes source of truth when enabled.
 */
class AgentProformaInvoiceShelfSync
{
    public function __construct(protected InvoiceShelfClient $client)
    {
    }

    public function sync(FatturaProforma $fattura, int $anno, int $mese): ?BillingDocument
    {
        $fattura->loadMissing(['righe', 'intestazione']);
        $periodo = sprintf('%04d_%02d', $anno, $mese);
        $agenteId = (int) ($fattura->intestazione?->user_id ?? 0);
        $idempotencyKey = BillingDocument::SOURCE_AGENT_PROFORMA.':'.$periodo.':agente:'.$agenteId.':fp:'.$fattura->id;

        $doc = BillingDocument::query()->firstOrNew(['idempotency_key' => $idempotencyKey]);
        $doc->source = BillingDocument::SOURCE_AGENT_PROFORMA;
        $doc->periodo = $periodo;
        $doc->idempotency_key = $idempotencyKey;
        $doc->status = $fattura->status ?? 'bozza';
        $doc->totale = (float) ($fattura->totale_imponibile ?? 0);
        $doc->gestiio_subject_type = FatturaProforma::class;
        $doc->gestiio_subject_id = $fattura->id;

        $items = $fattura->righe->map(fn ($r) => [
            'name' => (string) $r->descrizione,
            'description' => (string) ($r->dettaglio ?? ''),
            'quantity' => (float) ($r->quantita ?: 1),
            'price' => (float) $r->imponibile,
        ])->values()->all();

        $meta = [
            'fattura_proforma_id' => $fattura->id,
            'numero' => $fattura->numero,
            'agente_id' => $agenteId,
            'items' => $items,
            'synced_at' => now()->toIso8601String(),
            'offline' => ! $this->client->enabled(),
        ];

        if ($this->client->enabled()) {
            try {
                $customerId = $this->ensureAgenteCustomer($fattura->intestazione, $agenteId);
                $response = $this->client->createEstimate([
                    'estimate_date' => optional($fattura->data)->toDateString() ?? now()->toDateString(),
                    'expiry_date' => now()->addDays(30)->toDateString(),
                    'customer_id' => $customerId,
                    'status' => 'DRAFT',
                    'template_name' => config('invoiceshelf.estimate_template', 'estimate1'),
                    'notes' => 'Proforma agente #'.$fattura->numero.' — '.$periodo,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'discount_val' => 0,
                    'sub_total' => $doc->totale,
                    'total' => $doc->totale,
                    'tax' => 0,
                    'items' => collect($items)->map(fn ($item) => [
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'discount_type' => 'fixed',
                        'discount_val' => 0,
                        'discount' => 0,
                        'total' => $item['quantity'] * $item['price'],
                        'tax' => 0,
                        'taxes' => [],
                    ])->values()->all(),
                ]);
                $data = $response['data'] ?? $response;
                $doc->invoiceshelf_type = 'estimate';
                $doc->invoiceshelf_id = (int) ($data['id'] ?? 0) ?: null;
                $doc->unique_hash = $data['unique_hash'] ?? null;
                $meta['invoiceshelf'] = $data;
                $meta['offline'] = false;
            } catch (\Throwable $e) {
                Log::warning('AgentProformaInvoiceShelfSync failed', [
                    'fattura_id' => $fattura->id,
                    'error' => $e->getMessage(),
                ]);
                $meta['sync_error'] = $e->getMessage();
            }
        }

        $doc->meta = $meta;
        $doc->save();

        return $doc;
    }

    protected function ensureAgenteCustomer(?IntestazioneFatturaProforma $intestazione, int $agenteId): int
    {
        $email = null;
        if ($agenteId > 0) {
            $email = User::query()->whereKey($agenteId)->value('email');
        }
        $name = $intestazione?->denominazione ?: ('Agente #'.$agenteId);

        if ($email) {
            $list = $this->client->listCustomers(['search' => $email, 'limit' => 20]);
            $data = $list['data'] ?? $list;
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row) && strcasecmp((string) ($row['email'] ?? ''), $email) === 0) {
                        return (int) $row['id'];
                    }
                }
            }
        }

        $created = $this->client->createCustomer([
            'name' => $name,
            'email' => $email ?: ('agente'.$agenteId.'@gestiio.local'),
            'currency_id' => 1,
            'company_name' => $name,
            'contact_name' => $name,
            'billing' => [
                'name' => $name,
                'address_street_1' => (string) ($intestazione?->indirizzo ?? ''),
                'city' => (string) ($intestazione?->citta ?? ''),
                'zip' => (string) ($intestazione?->cap ?? ''),
            ],
        ]);

        return (int) ($created['data']['id'] ?? $created['id'] ?? 0);
    }
}

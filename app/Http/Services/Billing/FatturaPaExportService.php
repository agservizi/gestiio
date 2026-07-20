<?php

namespace App\Http\Services\Billing;

use App\Http\Services\InvoiceShelf\InvoiceShelfClient;
use App\Models\BillingDocument;
use RuntimeException;

class FatturaPaExportService
{
    public function __construct(
        protected InvoiceShelfClient $client,
        protected FatturaPaXmlBuilder $builder
    ) {
    }

    /**
     * @return array{xml: string, filename: string}
     */
    public function exportBillingDocument(BillingDocument $document): array
    {
        if ($document->invoiceshelf_type !== 'invoice' || ! $document->invoiceshelf_id) {
            throw new RuntimeException(
                'Export XML disponibile solo per fatture InvoiceShelf (converti la proforma in fattura prima).'
            );
        }

        return $this->exportInvoiceShelfId((int) $document->invoiceshelf_id);
    }

    /**
     * @return array{xml: string, filename: string}
     */
    public function exportInvoiceShelfId(int $invoiceId): array
    {
        $this->client->assertEnabled();
        $response = $this->client->getInvoice($invoiceId);
        $invoice = $response['data'] ?? $response;
        if (! is_array($invoice) || empty($invoice['id'])) {
            throw new RuntimeException('Fattura InvoiceShelf #'.$invoiceId.' non trovata.');
        }

        // Assicura customer completo (con billing + fields) se l'embed è parziale
        $customerId = (int) ($invoice['customer_id'] ?? $invoice['customer']['id'] ?? 0);
        $needsCustomer = $customerId > 0 && (
            empty($invoice['customer']['billing'])
            || ! is_array($invoice['customer']['billing'] ?? null)
        );
        if ($needsCustomer) {
            $cust = $this->client->getCustomer($customerId);
            $invoice['customer'] = $cust['data'] ?? $cust;
        }

        return $this->builder->buildFromInvoiceShelf($invoice);
    }
}

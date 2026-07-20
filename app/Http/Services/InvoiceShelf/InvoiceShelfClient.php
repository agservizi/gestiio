<?php

namespace App\Http\Services\InvoiceShelf;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InvoiceShelfClient
{
    public function enabled(): bool
    {
        return (bool) config('invoiceshelf.enabled')
            && (string) config('invoiceshelf.token') !== ''
            && (string) config('invoiceshelf.url') !== '';
    }

    public function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('InvoiceShelf non configurato (INVOICESHELF_ENABLED/TOKEN/URL).');
        }
    }

    public function healthCheck(): bool
    {
        try {
            $this->assertEnabled();
            $response = $this->http()->get('/api/v1/auth/check');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('InvoiceShelf health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function listCustomers(array $query = []): array
    {
        return $this->get('/api/v1/customers', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomer(int $id): array
    {
        return $this->get('/api/v1/customers/'.$id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCustomer(array $payload): array
    {
        return $this->post('/api/v1/customers', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createEstimate(array $payload): array
    {
        return $this->post('/api/v1/estimates', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEstimate(int $id): array
    {
        return $this->get('/api/v1/estimates/'.$id);
    }

    /**
     * @return array<string, mixed>
     */
    public function listEstimates(array $query = []): array
    {
        return $this->get('/api/v1/estimates', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateEstimateStatus(int $id, array $payload): array
    {
        return $this->post('/api/v1/estimates/'.$id.'/status', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function convertEstimateToInvoice(int $id): array
    {
        return $this->post('/api/v1/estimates/'.$id.'/convert-to-invoice');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createInvoice(array $payload): array
    {
        return $this->post('/api/v1/invoices', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInvoice(int $id): array
    {
        return $this->get('/api/v1/invoices/'.$id);
    }

    /**
     * @return array<string, mixed>
     */
    public function listInvoices(array $query = []): array
    {
        return $this->get('/api/v1/invoices', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPayment(array $payload): array
    {
        return $this->post('/api/v1/payments', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function listPayments(array $query = []): array
    {
        return $this->get('/api/v1/payments', $query);
    }

    public function downloadPdfByHash(string $type, string $uniqueHash): string
    {
        $this->assertEnabled();
        $path = $type === 'invoice'
            ? '/invoices/pdf/'.$uniqueHash
            : '/estimates/pdf/'.$uniqueHash;

        $response = Http::timeout((int) config('invoiceshelf.timeout', 30))
            ->withToken((string) config('invoiceshelf.token'))
            ->withHeaders([
                'company' => (string) config('invoiceshelf.company_id', 1),
                'Accept' => 'application/pdf',
            ])
            ->get(rtrim((string) config('invoiceshelf.url'), '/').$path);

        if (! $response->successful()) {
            throw new RuntimeException('Download PDF InvoiceShelf fallito (HTTP '.$response->status().').');
        }

        return $response->body();
    }

    /**
     * Ensure fornitore customer exists; returns InvoiceShelf customer id.
     */
    public function ensureFornitoreCustomer(): int
    {
        $configured = config('invoiceshelf.customer_fornitore_id');
        if ($configured) {
            return (int) $configured;
        }

        $email = (string) config('invoiceshelf.fornitore.email');
        $list = $this->listCustomers(['search' => $email, 'limit' => 20]);
        $data = $list['data'] ?? $list;
        if (is_array($data)) {
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (strcasecmp((string) ($row['email'] ?? ''), $email) === 0) {
                    return (int) $row['id'];
                }
            }
        }

        $created = $this->createCustomer([
            'name' => (string) config('invoiceshelf.fornitore.name'),
            'email' => $email,
            'currency_id' => 1,
            'company_name' => (string) config('invoiceshelf.fornitore.name'),
            'contact_name' => (string) config('invoiceshelf.fornitore.name'),
            'billing' => [
                'name' => (string) config('invoiceshelf.fornitore.name'),
            ],
        ]);

        $id = (int) ($created['data']['id'] ?? $created['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Impossibile creare customer fornitore su InvoiceShelf.');
        }

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        $this->assertEnabled();
        $response = $this->http()->get($path, $query);
        $this->throwIfFailed($response, 'GET '.$path);

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function post(string $path, array $payload = []): array
    {
        $this->assertEnabled();
        $response = $this->http()->post($path, $payload);
        $this->throwIfFailed($response, 'POST '.$path);

        return $response->json() ?? [];
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('invoiceshelf.url'), '/'))
            ->timeout((int) config('invoiceshelf.timeout', 30))
            ->acceptJson()
            ->withToken((string) config('invoiceshelf.token'))
            ->withHeaders([
                'company' => (string) config('invoiceshelf.company_id', 1),
            ]);
    }

    protected function throwIfFailed($response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->body();
        Log::error('InvoiceShelf API error', [
            'context' => $context,
            'status' => $response->status(),
            'body' => mb_substr($body, 0, 1000),
        ]);

        throw new RuntimeException(
            'InvoiceShelf API error ('.$context.'): HTTP '.$response->status()
        );
    }
}

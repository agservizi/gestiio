<?php

namespace App\Http\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenApiVisureService
{
    private const ENDPOINT_SANDBOX = 'https://test.visengine2.altravia.com/';
    private const ENDPOINT_PRODUCTION = 'https://visengine2.altravia.com/';

    protected PendingRequest $client;
    public ?string $message = null;
    protected ?string $bearerTokenOverride = null;

    public function __construct(?string $bearerToken = null)
    {
        $this->bearerTokenOverride = $bearerToken;
        $this->client = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken(),
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ])->timeout(30);
    }

    public function elencoVisure(): ?array
    {
        try {
            $response = $this->client->get($this->endpoint() . 'visure');
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $payload = $this->decode($response);

        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    public function creaRichiesta(string $hashVisura, array $dati, int $state = 1): ?array
    {
        $payload = [
            'hash_visura' => $hashVisura,
            'json_visura' => $dati,
            'state' => $state,
        ];

        try {
            $response = $this->client->post($this->endpoint() . 'richiesta', $payload);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $decoded = $this->decode($response);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function statoRichiesta(string $requestId): ?array
    {
        try {
            $response = $this->client->put($this->endpoint() . 'richiesta/' . urlencode($requestId), []);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $decoded = $this->decode($response);
        if (is_array($decoded['data'] ?? null)) {
            return $decoded['data'];
        }

        // Fallback per varianti endpoint.
        try {
            $fallback = $this->client->put($this->endpoint() . 'richiesta', [
                'request_id' => $requestId,
            ]);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $decoded = $this->decode($fallback);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function scaricaDocumento(string $requestId): ?array
    {
        try {
            $response = $this->client->get($this->endpoint() . 'documento/' . urlencode($requestId));
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $decoded = $this->decode($response);
        if (is_array($decoded['data'] ?? null)) {
            return $decoded['data'];
        }

        // Fallback per varianti endpoint.
        try {
            $fallback = $this->client->get($this->endpoint() . 'documento', ['request_id' => $requestId]);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }
        $decoded = $this->decode($fallback);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    protected function decode(Response $response): array
    {
        if ($response->successful()) {
            $json = $response->json();
            if (is_array($json)) {
                $this->message = $json['message'] ?? null;
                return $json;
            }

            return [];
        }

        $json = $response->json();
        if (is_array($json)) {
            $this->message = $json['message'] ?? ('Errore OpenAPI (' . $response->status() . ')');
            return $json;
        }

        $this->message = 'Errore OpenAPI (' . $response->status() . ')';
        return [];
    }

    protected function endpoint(): string
    {
        $customSandbox = config('services.openapi.visure_base_url_sandbox');
        $customProduction = config('services.openapi.visure_base_url_production');

        if (!config('services.openapi.sandbox')) {
            return rtrim((string) ($customProduction ?: self::ENDPOINT_PRODUCTION), '/') . '/';
        }

        return rtrim((string) ($customSandbox ?: self::ENDPOINT_SANDBOX), '/') . '/';
    }

    protected function bearerToken(): string
    {
        if ($this->bearerTokenOverride) {
            return $this->bearerTokenOverride;
        }

        return (string) (config('services.openapi.bearer_visure')
            ?: env('OPENAPI_BEARER_VISURE')
            ?: env('OPENAPI_BEARER'));
    }
}

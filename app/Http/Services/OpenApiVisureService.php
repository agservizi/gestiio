<?php

namespace App\Http\Services;

use Openapi\Client as OpenapiHttpClient;
use Throwable;

class OpenApiVisureService
{
    private const ENDPOINT_SANDBOX = 'https://test.visengine2.altravia.com/';

    private const ENDPOINT_PRODUCTION = 'https://visengine2.altravia.com/';

    protected OpenapiHttpClient $sdk;

    public ?string $message = null;

    protected ?string $bearerTokenOverride = null;

    public function __construct(?string $bearerToken = null)
    {
        $this->bearerTokenOverride = $bearerToken;
        $this->sdk = new OpenapiHttpClient($this->bearerToken());
    }

    public function elencoVisure(): ?array
    {
        try {
            $raw = $this->sdk->get($this->endpoint().'visure');
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $payload = $this->decodeRaw($raw);

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
            $raw = $this->sdk->post($this->endpoint().'richiesta', $payload);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $decoded = $this->decodeRaw($raw);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function statoRichiesta(string $requestId): ?array
    {
        try {
            $raw = $this->sdk->put($this->endpoint().'richiesta/'.urlencode($requestId), []);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $decoded = $this->decodeRaw($raw);
        if (is_array($decoded['data'] ?? null)) {
            return $decoded['data'];
        }

        try {
            $fallback = $this->sdk->put($this->endpoint().'richiesta', [
                'request_id' => $requestId,
            ]);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $decoded = $this->decodeRaw($fallback);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function scaricaDocumento(string $requestId): ?array
    {
        try {
            $raw = $this->sdk->get($this->endpoint().'documento/'.urlencode($requestId));
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $decoded = $this->decodeRaw($raw);
        if (is_array($decoded['data'] ?? null)) {
            return $decoded['data'];
        }

        try {
            $fallback = $this->sdk->get($this->endpoint().'documento', ['request_id' => $requestId]);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();

            return null;
        }
        $decoded = $this->decodeRaw($fallback);

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    protected function decodeRaw(string $raw): array
    {
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            $this->message = 'Risposta OpenAPI non valida';

            return [];
        }

        $this->message = isset($json['message']) ? (string) $json['message'] : null;

        return $json;
    }

    protected function endpoint(): string
    {
        $customSandbox = config('services.openapi.visure_base_url_sandbox');
        $customProduction = config('services.openapi.visure_base_url_production');

        if (! config('services.openapi.sandbox')) {
            return rtrim((string) ($customProduction ?: self::ENDPOINT_PRODUCTION), '/').'/';
        }

        return rtrim((string) ($customSandbox ?: self::ENDPOINT_SANDBOX), '/').'/';
    }

    protected function bearerToken(): string
    {
        if ($this->bearerTokenOverride) {
            return $this->bearerTokenOverride;
        }

        return (string) (config('services.openapi.bearer_visure'));
    }
}

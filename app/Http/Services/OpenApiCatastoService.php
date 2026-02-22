<?php

namespace App\Http\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenApiCatastoService
{
    private const ENDPOINT_SANDBOX = 'https://test.catasto.openapi.it/';
    private const ENDPOINT_PRODUCTION = 'https://catasto.openapi.it/';

    protected PendingRequest $client;
    public ?string $message = null;

    public function __construct()
    {
        $this->client = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken(),
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ])->timeout(45);
    }

    public function creaVisuraCatastale(array $payload): ?array
    {
        try {
            $response = $this->client->post($this->endpoint() . 'visura_catastale', $payload);
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }

        $decoded = $this->decodeJson($response);
        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function statoVisuraCatastale(string $id): ?array
    {
        try {
            $response = $this->client->get($this->endpoint() . 'visura_catastale/' . urlencode($id));
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }

        $decoded = $this->decodeJson($response);
        return is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
    }

    public function scaricaDocumentoVisuraCatastale(string $id): ?array
    {
        try {
            $response = $this->client->get($this->endpoint() . 'visura_catastale/' . urlencode($id) . '/documento');
        } catch (Throwable $e) {
            $this->message = $e->getMessage();
            return null;
        }

        $contentType = strtolower((string) $response->header('content-type'));
        if (str_contains($contentType, 'application/json')) {
            $decoded = $this->decodeJson($response);
            $data = $decoded['data'] ?? null;
            if (is_array($data)) {
                return $data;
            }
            return null;
        }

        if (!$response->successful()) {
            $this->message = 'Errore Catasto (' . $response->status() . ')';
            return null;
        }

        $disposition = (string) $response->header('content-disposition');
        $fileName = $this->extractFilenameFromDisposition($disposition) ?: ('visura_catastale_' . $id . '.pdf');

        return [
            'raw_content' => $response->body(),
            'mime_type' => (string) $response->header('content-type') ?: 'application/pdf',
            'nome' => $fileName,
        ];
    }

    protected function decodeJson(Response $response): array
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
            $this->message = $json['message'] ?? ('Errore Catasto (' . $response->status() . ')');
            return $json;
        }
        $this->message = 'Errore Catasto (' . $response->status() . ')';
        return [];
    }

    protected function endpoint(): string
    {
        $customSandbox = config('services.openapi.catasto_base_url_sandbox');
        $customProduction = config('services.openapi.catasto_base_url_production');

        if (!config('services.openapi.sandbox')) {
            return rtrim((string) ($customProduction ?: self::ENDPOINT_PRODUCTION), '/') . '/';
        }

        return rtrim((string) ($customSandbox ?: self::ENDPOINT_SANDBOX), '/') . '/';
    }

    protected function bearerToken(): string
    {
        return (string) (config('services.openapi.bearer_catasto')
            ?: env('OPENAPI_BEARER_CATASTO')
            ?: config('services.openapi.bearer_visure')
            ?: env('OPENAPI_BEARER'));
    }

    protected function extractFilenameFromDisposition(string $disposition): ?string
    {
        if (preg_match("/filename\\*?=(?:UTF-8''|UTF-8\\\\'\\\\')?\"?([^\";]+)/i", $disposition, $matches)) {
            return trim(rawurldecode($matches[1]));
        }
        return null;
    }
}

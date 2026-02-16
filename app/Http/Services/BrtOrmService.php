<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use Illuminate\Support\Facades\Http;

class BrtOrmService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)config('services.brt.orm_base_url'), '/');
        $this->apiKey = config('services.brt.orm_api_key');
    }

    public function create(array $orders): array
    {
        $url = $this->ormEndpoint('/colreqs');

        return $this->requestJson('post', $url, $orders);
    }

    public function delete(string $reservationId): array
    {
        $url = $this->ormEndpoint('/colreqs/' . $reservationId);

        return $this->requestJson('delete', $url, []);
    }

    protected function requestJson(string $method, string $url, array $payload): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return [
                'success' => false,
                'message' => 'Configurazione ORM BRT mancante (BRT_ORM_BASE_URL o BRT_ORM_API_KEY)',
            ];
        }

        $log = new ChiamataApi();
        $log->servizio = 'brt-orm';
        $log->url = $url;
        $log->request = $payload;
        $log->method = $method;
        $log->save();

        $request = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        if ($method === 'delete') {
            $res = $request->delete($url);
        } else {
            $res = $request->post($url, $payload);
        }

        $json = $res->json();
        $log->status = $res->status();
        $log->response = is_array($json) ? $json : ['raw' => $res->body()];
        $log->save();

        if (!is_array($json)) {
            return [
                'success' => $res->successful(),
                'status' => $res->status(),
                'raw' => $res->body(),
            ];
        }

        return $json;
    }

    protected function ormEndpoint(string $suffix): string
    {
        $base = rtrim($this->baseUrl, '/');

        if (str_ends_with($base, '/api/geodata/v410')) {
            return $base . $suffix;
        }

        return $base . '/api/geodata/v410' . $suffix;
    }
}

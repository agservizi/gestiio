<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use App\Models\SpedizioneInpost;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InpostService
{
    public const DEFAULT_API_BASE_URL = 'https://api.inpost-group.com';
    public const DEFAULT_TOKEN_URL = 'https://api.inpost-group.com/oauth2/token';

    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $scope;
    protected string $organizationId;
    protected string $locationEndpoint;
    protected string $trackingEndpointTemplate;
    protected string $labelEndpointTemplate;
    protected string $senderType;
    protected string $senderPointId;
    protected array $senderAddress;
    protected array $defaultHeaders;
    protected string $trackingPortalBaseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.inpost.base_url', self::DEFAULT_API_BASE_URL), '/');
        $this->tokenUrl = (string) config('services.inpost.token_url', self::DEFAULT_TOKEN_URL);
        $this->clientId = (string) config('services.inpost.client_id');
        $this->clientSecret = (string) config('services.inpost.client_secret');
        $this->scope = trim((string) config('services.inpost.scope', 'openid api:points:read api:shipments:read api:shipments:write api:tracking:read'));
        $this->organizationId = (string) config('services.inpost.organization_id');
        $this->locationEndpoint = (string) config('services.inpost.location_endpoint', '/location/v1/points');
        $this->trackingEndpointTemplate = (string) config('services.inpost.tracking_endpoint_template', '/tracking/v1/shipments/{trackingNumber}');
        $this->labelEndpointTemplate = (string) config('services.inpost.label_endpoint_template', '/shipping/v2/organizations/{organizationId}/shipments/{shipmentId}/label');
        $this->senderType = (string) config('services.inpost.sender_type', 'address');
        $this->senderPointId = (string) config('services.inpost.sender_point_id', '');
        $this->senderAddress = [
            'name' => (string) config('services.inpost.sender_name', ''),
            'email' => (string) config('services.inpost.sender_email', ''),
            'phone' => (string) config('services.inpost.sender_phone', ''),
            'street' => (string) config('services.inpost.sender_street', ''),
            'buildingNumber' => (string) config('services.inpost.sender_building_number', ''),
            'postCode' => (string) config('services.inpost.sender_post_code', ''),
            'city' => (string) config('services.inpost.sender_city', ''),
            'countryCode' => strtoupper((string) config('services.inpost.sender_country_code', 'PL')),
        ];
        $this->defaultHeaders = $this->parseJsonArrayConfig(config('services.inpost.default_headers'));
        $this->trackingPortalBaseUrl = rtrim((string) config('services.inpost.tracking_portal_base_url', 'https://inpost.pl/en/help/find-parcel'), '/');
    }

    public function shipment(SpedizioneInpost $record): array
    {
        $endpoint = $this->shipmentEndpoint($record);
        $url = $this->baseUrl . $endpoint;
        $payload = $this->buildShipmentPayload($record);

        return $this->requestJson('post', $url, $payload, $record);
    }

    public function buildShipmentPayload(SpedizioneInpost $record): array
    {
        return $this->shipmentPayload($record);
    }

    public function tracking(string $trackingNumber): array
    {
        $path = str_replace('{trackingNumber}', urlencode($trackingNumber), $this->trackingEndpointTemplate);

        return $this->requestJson('get', $this->baseUrl . $path);
    }

    public function points(string $countryCode, string $city = '', string $postCode = ''): array
    {
        $query = array_filter([
            'countryCode' => strtoupper($countryCode),
            'city' => $city,
            'postCode' => $postCode,
        ], fn ($value) => filled($value));

        $response = $this->requestJson('get', $this->baseUrl . $this->locationEndpoint, $query);

        return [
            'raw' => $response,
            'points' => $this->normalizePoints($response),
        ];
    }

    public function label(SpedizioneInpost $record): ?array
    {
        $base64 = $this->extractBase64Label($record->response ?? []);
        if ($base64) {
            return [
                'content' => base64_decode($base64),
                'content_type' => 'application/pdf',
                'filename' => 'inpost-label-' . $record->id . '.pdf',
            ];
        }

        $url = $record->label_url ?: $this->extractLabelUrl($record->response ?? []);
        if ($url) {
            $raw = $this->requestRaw('get', $url, [], $record);

            return [
                'content' => $raw['body'],
                'content_type' => $raw['content_type'] ?: 'application/pdf',
                'filename' => 'inpost-label-' . $record->id . '.pdf',
            ];
        }

        if ($record->shipment_uuid) {
            $path = str_replace(
                ['{organizationId}', '{shipmentId}'],
                [$this->organizationId, urlencode($record->shipment_uuid)],
                $this->labelEndpointTemplate
            );
            $raw = $this->requestRaw('get', $this->baseUrl . $path, [
                'format' => strtoupper((string) config('services.inpost.label_format', 'PDF')),
            ], $record);

            return [
                'content' => $raw['body'],
                'content_type' => $raw['content_type'] ?: 'application/pdf',
                'filename' => 'inpost-label-' . $record->id . '.pdf',
            ];
        }

        return null;
    }

    public function trackingUrl(string $trackingNumber): string
    {
        return $this->trackingPortalBaseUrl . '?number=' . urlencode($trackingNumber);
    }

    protected function shipmentEndpoint(SpedizioneInpost $record): string
    {
        $type = $record->delivery_type === 'address' ? 'address-to-address' : 'address-to-point';

        return '/shipping/v2/organizations/' . $this->organizationId . '/shipments/' . $type;
    }

    protected function shipmentPayload(SpedizioneInpost $record): array
    {
        $payload = [
            'customerReference' => (string) $record->id,
            'sender' => $this->senderPayload($record),
            'receiver' => $this->receiverPayload($record),
            'parcels' => $this->parcelPayload($record),
            'outputFormat' => strtoupper((string) config('services.inpost.label_format', 'PDF')),
        ];

        $annotation = trim((string) data_get($record->altri_dati, 'annotation', ''));
        if ($annotation !== '') {
            $payload['annotation'] = $annotation;
        }

        if ($record->delivery_type === 'point') {
            $payload['destinationPoint'] = [
                'id' => $record->punto_inpost_id,
                'name' => $record->punto_inpost_label,
            ];
        } else {
            $payload['destinationAddress'] = $this->destinationAddressPayload($record);
        }

        $defaults = $this->parseJsonArrayConfig(config('services.inpost.defaults'));
        if ($defaults) {
            $payload = array_replace_recursive($payload, $defaults);
        }

        return $payload;
    }

    protected function senderPayload(SpedizioneInpost $record): array
    {
        $payload = [
            'name' => $record->nome_mittente ?: $this->senderAddress['name'],
            'email' => $record->email_mittente ?: $this->senderAddress['email'],
            'phone' => $record->mobile_mittente ?: $this->senderAddress['phone'],
        ];

        if ($this->senderType === 'point' && $this->senderPointId) {
            $payload['point'] = [
                'id' => $this->senderPointId,
            ];
        } else {
            $payload['address'] = [
                'street' => $this->senderAddress['street'],
                'buildingNumber' => $this->senderAddress['buildingNumber'],
                'postCode' => $this->senderAddress['postCode'],
                'city' => $this->senderAddress['city'],
                'countryCode' => $this->senderAddress['countryCode'],
            ];
        }

        return $payload;
    }

    protected function receiverPayload(SpedizioneInpost $record): array
    {
        return [
            'name' => $record->ragione_sociale_destinatario,
            'email' => $record->email_destinatario,
            'phone' => $record->mobile_referente_consegna,
        ];
    }

    protected function destinationAddressPayload(SpedizioneInpost $record): array
    {
        return [
            'street' => $record->indirizzo_destinatario,
            'postCode' => $record->cap_destinatario,
            'city' => $record->localita_destinazione,
            'countryCode' => $record->nazione_destinazione,
            'province' => $record->provincia_destinatario,
        ];
    }

    protected function parcelPayload(SpedizioneInpost $record): array
    {
        $colli = is_array($record->dati_colli) ? $record->dati_colli : [];
        $parcels = [];

        foreach ($colli as $index => $collo) {
            $length = (float) ($collo['profondita'] ?? 0);
            $width = (float) ($collo['larghezza'] ?? 0);
            $height = (float) ($collo['altezza'] ?? 0);
            $weightKg = (float) ($collo['peso_reale'] ?? 0);
            $reference = trim((string) data_get($record->altri_dati, 'package_reference', ''));

            $parcels[] = [
                'reference' => $reference !== '' ? $reference : (string) ($index + 1),
                'dimensions' => [
                    'length' => (int) round($length),
                    'width' => (int) round($width),
                    'height' => (int) round($height),
                    'unit' => 'cm',
                ],
                'weight' => [
                    'value' => (int) round($weightKg * 1000),
                    'unit' => 'g',
                ],
            ];
        }

        return $parcels;
    }

    protected function requestJson(string $method, string $url, array $payload = [], ?SpedizioneInpost $record = null): array
    {
        $request = $this->authorizedRequest();
        $log = $this->makeLog($method, $url, $payload, $record);

        $response = $method === 'get'
            ? $request->get($url, $payload)
            : $request->{$method}($url, $payload);

        $json = $response->json();
        $log->status = $response->status();
        $log->response = is_array($json) ? $json : ['raw' => $response->body()];
        $log->save();

        return is_array($json) ? $json : ['raw' => $response->body()];
    }

    protected function requestRaw(string $method, string $url, array $payload = [], ?SpedizioneInpost $record = null): array
    {
        $request = $this->authorizedRequest();
        $log = $this->makeLog($method, $url, $payload, $record);

        $response = $method === 'get'
            ? $request->get($url, $payload)
            : $request->{$method}($url, $payload);

        $log->status = $response->status();
        $log->response = [
            'content_type' => $response->header('Content-Type'),
            'preview' => Str::limit($response->body(), 2000),
        ];
        $log->save();

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type'),
            'status' => $response->status(),
        ];
    }

    protected function authorizedRequest()
    {
        $token = $this->accessToken();

        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders($this->defaultHeaders);
    }

    protected function accessToken(): string
    {
        return Cache::remember('inpost_access_token', 240, function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'scope' => $this->scope,
                ])
                ->throw()
                ->json();

            $ttl = max(60, (int) ($response['expires_in'] ?? 300) - 30);
            Cache::put('inpost_access_token', (string) $response['access_token'], $ttl);

            return (string) $response['access_token'];
        });
    }

    protected function makeLog(string $method, string $url, array $payload = [], ?SpedizioneInpost $record = null): ChiamataApi
    {
        $log = new ChiamataApi();
        $log->servizio = 'inpost';
        $log->url = $url;
        $log->request = $payload;
        $log->method = $method;

        if ($record) {
            $log->service_id = $record->id;
            $log->service_type = get_class($record);
        }

        $log->save();

        return $log;
    }

    protected function normalizePoints(array $response): array
    {
        $items = Arr::wrap($response['items'] ?? $response['points'] ?? $response['data'] ?? $response);

        return collect($items)->map(function ($point) {
            $id = data_get($point, 'id') ?: data_get($point, 'name');
            $name = data_get($point, 'name') ?: data_get($point, 'location.name') ?: $id;
            $address = implode(', ', array_filter([
                data_get($point, 'address.line1'),
                data_get($point, 'address.street'),
                data_get($point, 'address.postCode') ?: data_get($point, 'address.postalCode'),
                data_get($point, 'address.city'),
            ]));

            return [
                'id' => $id,
                'name' => $name,
                'address' => $address,
            ];
        })->filter(fn ($item) => filled($item['id']))->values()->all();
    }

    protected function extractBase64Label(array $response): ?string
    {
        $paths = [
            'label.content',
            'label.base64',
            'labels.0.content',
            'labels.0.base64',
            'shipment.label.content',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function extractLabelUrl(array $response): ?string
    {
        $paths = [
            'label.url',
            'labels.0.url',
            'shipment.labelUrl',
            'shipment.label.url',
            'links.label',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function parseJsonArrayConfig($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

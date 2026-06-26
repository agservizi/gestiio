<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use App\Models\InpostPickup;
use App\Models\InpostReturn;
use App\Models\SpedizioneInpost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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

    protected string $accountEndpoint;

    protected string $depositsEndpoint;

    protected string $trackingEndpointTemplate;

    protected string $labelEndpointTemplate;

    protected string $shipmentReadEndpointTemplate;

    protected string $returnReadEndpointTemplate;

    protected string $pickupListEndpointTemplate;

    protected string $pickupReadEndpointTemplate;

    protected string $pickupCreateEndpointTemplate;

    protected string $pickupCancelEndpointTemplate;

    protected string $pickupCutoffEndpoint;

    protected string $defaultCountry;

    protected int $pointSearchLimit;

    protected string $itPointsBaseUrl;

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
        $this->accountEndpoint = (string) config('services.inpost.account_endpoint', '/account/v1/organizations');
        $this->depositsEndpoint = (string) config('services.inpost.deposits_endpoint', '/deposits/v1/organizations/{organizationId}/deposits');
        $this->trackingEndpointTemplate = (string) config('services.inpost.tracking_endpoint_template', '/tracking/v1/shipments/{trackingNumber}');
        $this->labelEndpointTemplate = (string) config('services.inpost.label_endpoint_template', '/shipping/v2/organizations/{organizationId}/shipments/{shipmentId}/label');
        $this->shipmentReadEndpointTemplate = (string) config('services.inpost.shipment_read_endpoint_template', '/shipping/v2/organizations/{organizationId}/shipments/{shipmentId}');
        $this->returnReadEndpointTemplate = (string) config('services.inpost.return_read_endpoint_template', '/returns/v2/organizations/{organizationId}/returns/{returnId}');
        $this->pickupListEndpointTemplate = (string) config('services.inpost.pickup_list_endpoint_template', '/pickups/v1/organizations/{organizationId}/one-time-pickups');
        $this->pickupReadEndpointTemplate = (string) config('services.inpost.pickup_read_endpoint_template', '/pickups/v1/organizations/{organizationId}/one-time-pickups/{orderId}');
        $this->pickupCreateEndpointTemplate = (string) config('services.inpost.pickup_create_endpoint_template', '/pickups/v1/organizations/{organizationId}/one-time-pickups');
        $this->pickupCancelEndpointTemplate = (string) config('services.inpost.pickup_cancel_endpoint_template', '/pickups/v1/organizations/{organizationId}/one-time-pickups/{orderId}/cancel');
        $this->pickupCutoffEndpoint = (string) config('services.inpost.pickup_cutoff_endpoint', '/pickups/v1/cutoff-time');
        $this->defaultCountry = strtoupper((string) config('services.inpost.default_country', 'IT'));
        $this->pointSearchLimit = max(1, (int) config('services.inpost.point_search_limit', 20));
        $this->itPointsBaseUrl = rtrim((string) config('services.inpost.it_points_base_url', 'https://api-shipx-it.easypack24.net'), '/');
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
        $url = $this->baseUrl.$endpoint;
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

        return $this->requestJson('get', $this->baseUrl.$path);
    }

    public function getShipment(string $shipmentId, ?SpedizioneInpost $record = null): array
    {
        $path = str_replace(
            ['{organizationId}', '{shipmentId}'],
            [$this->organizationId, urlencode($shipmentId)],
            $this->shipmentReadEndpointTemplate
        );

        return $this->requestJson('get', $this->baseUrl.$path, [], $record);
    }

    public function points(string $countryCode, string $city = '', string $postCode = ''): array
    {
        $countryCode = strtoupper(trim($countryCode ?: $this->defaultCountry));
        if ($countryCode === 'IT' && $this->itPointsBaseUrl !== '') {
            $italyResult = $this->italyPoints($countryCode, $city, $postCode);
            if (! empty($italyResult['points'])) {
                return $italyResult;
            }
        }

        $query = array_filter([
            'countryCode' => $countryCode,
            'city' => $city,
            'postCode' => $postCode,
        ], fn ($value) => filled($value));

        try {
            $response = $this->requestJson('get', $this->baseUrl.$this->locationEndpoint, $query);
            $normalized = $this->normalizePoints($response, $countryCode, $city, $postCode);

            if ($countryCode === 'IT' && $this->itPointsBaseUrl !== '' && empty($normalized)) {
                $italyResult = $this->italyPoints($countryCode, $city, $postCode);
                if (! empty($italyResult['points'])) {
                    return $italyResult;
                }
            }

            return [
                'raw' => $response,
                'points' => $normalized,
                'source' => 'global',
                'error' => null,
            ];
        } catch (Throwable $e) {
            if ($countryCode === 'IT' && $this->itPointsBaseUrl !== '') {
                $fallback = $this->italyPoints($countryCode, $city, $postCode);
                $fallback['error'] = $fallback['points'] ? null : $e->getMessage();

                return $fallback;
            }

            return [
                'raw' => [],
                'points' => [],
                'source' => 'global',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function italyPoints(string $countryCode, string $city = '', string $postCode = ''): array
    {
        $queryText = trim(implode(' ', array_filter([$city, $postCode])));
        $query = array_filter([
            'country_code' => $countryCode,
            'limit' => $this->pointSearchLimit,
            'query' => $queryText !== '' ? $queryText : null,
        ], fn ($value) => filled($value));

        try {
            $response = Http::acceptJson()->get($this->itPointsBaseUrl.'/v1/points', $query)->json();

            return [
                'raw' => is_array($response) ? $response : [],
                'points' => $this->normalizeItalyPoints(is_array($response) ? $response : [], $countryCode, $city, $postCode),
                'source' => 'it_shipx',
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'raw' => [],
                'points' => [],
                'source' => 'it_shipx',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function label(SpedizioneInpost $record): ?array
    {
        $base64 = $this->extractBase64Label($record->response ?? []);
        if ($base64) {
            return [
                'content' => base64_decode($base64),
                'content_type' => 'application/pdf',
                'filename' => 'inpost-label-'.$record->id.'.pdf',
            ];
        }

        $url = $record->label_url ?: $this->extractLabelUrl($record->response ?? []);
        if ($url) {
            $raw = $this->requestRaw('get', $url, [], $record);
            if (($raw['status'] ?? 200) >= 400) {
                return null;
            }

            return [
                'content' => $raw['body'],
                'content_type' => $raw['content_type'] ?: 'application/pdf',
                'filename' => 'inpost-label-'.$record->id.'.pdf',
            ];
        }

        if ($record->shipment_uuid) {
            $path = str_replace(
                ['{organizationId}', '{shipmentId}'],
                [$this->organizationId, urlencode($record->shipment_uuid)],
                $this->labelEndpointTemplate
            );
            $raw = $this->requestRaw(
                'get',
                $this->baseUrl.$path,
                [],
                $record,
                ['Accept' => $this->labelAcceptHeader()]
            );
            if (($raw['status'] ?? 200) >= 400) {
                return null;
            }

            return [
                'content' => $raw['body'],
                'content_type' => $raw['content_type'] ?: 'application/pdf',
                'filename' => 'inpost-label-'.$record->id.'.pdf',
            ];
        }

        return null;
    }

    public function trackingUrl(string $trackingNumber): string
    {
        return $this->trackingPortalBaseUrl.'?number='.urlencode($trackingNumber);
    }

    public function account(): array
    {
        return $this->requestJson('get', $this->baseUrl.$this->replaceOrganization($this->accountEndpoint));
    }

    public function deposits(array $query = []): array
    {
        return $this->requestJson('get', $this->baseUrl.$this->replaceOrganization($this->depositsEndpoint), $query);
    }

    public function createDeposit(array $payload): array
    {
        return $this->requestJson('post', $this->baseUrl.$this->replaceOrganization($this->depositsEndpoint), $payload);
    }

    public function extractTrackingNumber(array $response): ?string
    {
        $paths = [
            'trackingNumber',
            'tracking.number',
            'shipment.trackingNumber',
            'shipment.tracking.number',
            'parcels.0.trackingNumber',
            'parcels.0.tracking.number',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function extractShipmentStatus(array $response): ?string
    {
        $paths = [
            'status',
            'currentStatus',
            'shipment.status',
            'shipment.currentStatus',
            'trackingResponse.status',
            'trackingResponse.currentStatus',
            'eventCode',
            'event.code',
            'events.0.eventCode',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function extractStatusText(array $response): ?string
    {
        $paths = [
            'message',
            'description',
            'statusDescription',
            'eventDescription',
            'event.description',
            'events.0.description',
            'error.message',
            'errors.0.message',
            'violations.0.message',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function extractLabelUrlFromResponse(array $response): ?string
    {
        return $this->extractLabelUrl($response);
    }

    public function createReturn(InpostReturn $record): array
    {
        $endpoint = '/returns/v2/organizations/'.$this->organizationId.'/returns';
        $payload = $this->returnPayload($record);

        return $this->requestJson('post', $this->baseUrl.$endpoint, $payload, $record);
    }

    public function buildReturnPayload(InpostReturn $record): array
    {
        return $this->returnPayload($record);
    }

    public function requestRawPublic(string $method, string $url, array $payload = [], ?Model $record = null, array $headers = []): array
    {
        return $this->requestRaw($method, $url, $payload, $record, $headers);
    }

    public function buildPickupPayload(InpostPickup $record): array
    {
        return $this->pickupPayload($record);
    }

    public function createPickup(InpostPickup $record): array
    {
        $endpoint = $this->replaceOrganization($this->pickupCreateEndpointTemplate);
        $payload = $this->pickupPayload($record);

        return $this->requestJson('post', $this->baseUrl.$endpoint, $payload, $record);
    }

    public function pickups(array $query = []): array
    {
        return $this->requestJson('get', $this->baseUrl.$this->replaceOrganization($this->pickupListEndpointTemplate), $query);
    }

    public function getPickup(string $orderId, ?InpostPickup $record = null): array
    {
        $endpoint = str_replace('{orderId}', urlencode($orderId), $this->replaceOrganization($this->pickupReadEndpointTemplate));

        return $this->requestJson('get', $this->baseUrl.$endpoint, [], $record);
    }

    public function cancelPickup(InpostPickup $record): array
    {
        $endpoint = str_replace('{orderId}', urlencode((string) $record->remote_id), $this->replaceOrganization($this->pickupCancelEndpointTemplate));

        return $this->requestJson('put', $this->baseUrl.$endpoint, [], $record);
    }

    public function pickupCutoffTime(array $query = []): array
    {
        $endpoint = $this->replaceOrganization($this->pickupCutoffEndpoint);

        return $this->requestJson('get', $this->baseUrl.$endpoint, $query);
    }

    public function getReturn(string $returnId, ?InpostReturn $record = null): array
    {
        $endpoint = str_replace('{returnId}', urlencode($returnId), $this->replaceOrganization($this->returnReadEndpointTemplate));

        return $this->requestJson('get', $this->baseUrl.$endpoint, [], $record);
    }

    protected function returnPayload(InpostReturn $record): array
    {
        return [
            'receiver' => [
                'name' => $record->receiver_name,
                'email' => $record->receiver_email ?: null,
                'phone' => $record->receiver_phone ?: null,
            ],
            'custom_attributes' => [
                'target_point' => $record->point_id,
            ],
            'reference' => $record->customer_reference ?: (string) $record->id,
        ];
    }

    protected function pickupPayload(InpostPickup $record): array
    {
        $payload = [
            'pickup_from' => $record->pickup_date->format('Y-m-d').'T09:00:00',
            'pickup_to' => $record->pickup_date->format('Y-m-d').'T18:00:00',
            'address' => [
                'street' => $record->street,
                'building_number' => $record->building_number ?: '',
                'post_code' => $record->post_code,
                'city' => $record->city,
                'country_code' => $record->country_code ?: 'IT',
            ],
            'contact_details' => [
                'name' => $record->contact_name,
                'email' => $record->contact_email ?: null,
                'phone' => $record->contact_phone,
            ],
            'parcel_count' => (int) ($record->parcel_count ?: 1),
            'reference' => $record->customer_reference ?: (string) $record->id,
        ];

        if ($record->note) {
            $payload['location_description'] = $record->note;
        }

        return $payload;
    }

    protected function shipmentEndpoint(SpedizioneInpost $record): string
    {
        $type = $record->delivery_type === 'address' ? 'address-to-address' : 'address-to-point';

        return '/shipping/v2/organizations/'.$this->organizationId.'/shipments/'.$type;
    }

    protected function shipmentPayload(SpedizioneInpost $record): array
    {
        $payload = [
            'customerReference' => (string) $record->id,
            'sender' => $this->senderPayload($record),
            'receiver' => $this->receiverPayload($record),
            'parcels' => $this->parcelPayload($record),
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

    protected function requestJson(string $method, string $url, array $payload = [], ?Model $record = null, array $headers = []): array
    {
        $request = $this->authorizedRequest($headers);
        $log = $this->makeLog($method, $url, $payload, $record);

        $response = $method === 'get'
            ? $request->get($url, $payload)
            : $request->{$method}($url, $payload);

        $json = $response->json();
        $log->status = $response->status();
        $body = is_array($json) ? $json : ['raw' => $response->body()];
        $body['_http_status'] = $response->status();

        if ($response->failed()) {
            $body['error'] = $body['error'] ?? true;
            $body['message'] = $body['message'] ?? $response->reason();
        }

        $log->response = $body;
        $log->save();

        return $body;
    }

    protected function replaceOrganization(string $path): string
    {
        return str_replace('{organizationId}', $this->organizationId, $path);
    }

    protected function requestRaw(string $method, string $url, array $payload = [], ?Model $record = null, array $headers = []): array
    {
        $request = $this->authorizedRequest($headers);
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

    protected function authorizedRequest(array $headers = [])
    {
        $token = $this->accessToken();

        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders(array_merge($this->defaultHeaders, $headers));
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

    protected function makeLog(string $method, string $url, array $payload = [], ?Model $record = null): ChiamataApi
    {
        $log = new ChiamataApi;
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

    protected function normalizePoints(array $response, string $countryCode = '', string $city = '', string $postCode = ''): array
    {
        $items = Arr::wrap($response['items'] ?? $response['points'] ?? $response['data'] ?? $response);
        $countryCode = strtoupper(trim($countryCode));
        $city = mb_strtolower(trim($city));
        $postCode = trim($postCode);

        return collect($items)
            ->map(function ($point) {
                $id = data_get($point, 'id') ?: data_get($point, 'name');
                $name = data_get($point, 'name') ?: $id;
                $pointCountry = strtoupper((string) (data_get($point, 'country') ?: data_get($point, 'address.country')));
                $pointCity = (string) data_get($point, 'address.city');
                $pointPostCode = (string) (data_get($point, 'address.postCode') ?: data_get($point, 'address.postalCode'));
                $description = (string) (data_get($point, 'description.content') ?: data_get($point, 'description'));
                $address = implode(', ', array_filter([
                    data_get($point, 'address.street'),
                    data_get($point, 'address.buildingNumber'),
                    $pointPostCode,
                    $pointCity,
                    $description,
                ]));

                return [
                    'id' => $id,
                    'name' => $name,
                    'address' => $address,
                    'country' => $pointCountry,
                    'city' => $pointCity,
                    'post_code' => $pointPostCode,
                ];
            })
            ->filter(function ($item) use ($countryCode, $city, $postCode) {
                if (! filled($item['id'])) {
                    return false;
                }

                if ($countryCode !== '' && strtoupper((string) $item['country']) !== $countryCode) {
                    return false;
                }

                if ($city !== '' && ! str_contains(mb_strtolower((string) $item['city']), $city)) {
                    return false;
                }

                if ($postCode !== '' && ! str_contains((string) $item['post_code'], $postCode)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    protected function normalizeItalyPoints(array $response, string $countryCode = '', string $city = '', string $postCode = ''): array
    {
        $items = Arr::wrap($response['items'] ?? $response);
        $countryCode = strtoupper(trim($countryCode));
        $city = mb_strtolower(trim($city));
        $postCode = trim($postCode);

        return collect($items)
            ->map(function ($point) {
                $id = (string) (data_get($point, 'name') ?: data_get($point, 'id'));
                $name = (string) (data_get($point, 'name') ?: $id);
                $pointCity = (string) (data_get($point, 'address_details.city') ?: data_get($point, 'address.city'));
                $pointPostCode = (string) (data_get($point, 'address_details.post_code') ?: data_get($point, 'address.postCode'));
                $street = (string) (data_get($point, 'address_details.street') ?: data_get($point, 'address.street'));
                $buildingNumber = (string) (data_get($point, 'address_details.building_number') ?: data_get($point, 'address.buildingNumber'));
                $description = (string) (data_get($point, 'location_description') ?: data_get($point, 'description'));
                $address = implode(', ', array_filter([
                    trim($street.' '.$buildingNumber),
                    $pointPostCode,
                    $pointCity,
                    $description,
                ]));

                return [
                    'id' => $id,
                    'name' => $name,
                    'address' => $address,
                    'country' => strtoupper((string) (data_get($point, 'address_details.country') ?: data_get($point, 'address.country') ?: 'IT')),
                    'city' => $pointCity,
                    'post_code' => $pointPostCode,
                ];
            })
            ->filter(function ($item) use ($countryCode, $city, $postCode) {
                if (! filled($item['id'])) {
                    return false;
                }

                if ($countryCode !== '' && strtoupper((string) $item['country']) !== $countryCode) {
                    return false;
                }

                if ($city !== '' && ! str_contains(mb_strtolower((string) $item['city']), $city)) {
                    return false;
                }

                if ($postCode !== '' && ! str_contains((string) $item['post_code'], $postCode)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
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

    protected function labelAcceptHeader(): string
    {
        $format = strtoupper(trim((string) config('services.inpost.label_format', 'A6')));

        return match ($format) {
            'A4' => 'application/pdf;format=A4',
            'A6', 'PDF', 'PDF_A6' => 'application/pdf;format=A6',
            'PDF_A4' => 'application/pdf;format=A4',
            'A4_BASE64', 'BASE64_A4', 'JSON_A4' => 'application/pdf+json;format=A4',
            'A6_BASE64', 'BASE64_A6', 'JSON_A6' => 'application/pdf+json;format=A6',
            default => str_contains($format, '/')
                ? (string) config('services.inpost.label_format')
                : 'application/pdf;format=A6',
        };
    }

    protected function parseJsonArrayConfig($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

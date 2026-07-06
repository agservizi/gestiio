<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\ChiamataApi;
use App\Models\SpedizioneInpost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InpostWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        if (! $this->signatureIsValid($request, $payload)) {
            Log::warning('Webhook InPost non valido: firma non riconosciuta');

            return response('Invalid InPost webhook', 400);
        }

        $data = $request->all();
        $record = $this->findShipment($data);
        $log = $this->logWebhook($request, $data, $record);

        if (! $record) {
            Log::warning('Webhook InPost ricevuto senza spedizione collegata', [
                'tracking' => $this->extractTrackingNumber($data),
                'shipment_uuid' => $this->extractShipmentId($data),
                'log_id' => $log->id,
            ]);

            return response()->json(['ok' => true, 'matched' => false]);
        }

        $this->applyWebhook($record, $data);

        return response()->json(['ok' => true, 'matched' => true, 'id' => $record->id]);
    }

    protected function signatureIsValid(Request $request, string $payload): bool
    {
        $secret = (string) config('services.inpost.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $plainSecret = $request->header('X-Webhook-Secret');
        if ($plainSecret && hash_equals($secret, $plainSecret)) {
            return true;
        }

        $signature = $request->header('X-InPost-Signature')
            ?: $request->header('InPost-Signature')
            ?: $request->header('X-Signature')
            ?: $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        $signature = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        return hash_equals($expected, $signature);
    }

    protected function findShipment(array $data): ?SpedizioneInpost
    {
        $tracking = $this->extractTrackingNumber($data);
        $shipmentId = $this->extractShipmentId($data);

        if (! $tracking && ! $shipmentId) {
            return null;
        }

        return SpedizioneInpost::withoutGlobalScopes()
            ->where(function ($query) use ($tracking, $shipmentId) {
                if ($tracking) {
                    $query->orWhere('tracking_number', $tracking);
                }

                if ($shipmentId) {
                    $query->orWhere('shipment_uuid', $shipmentId);
                }
            })
            ->latest('id')
            ->first();
    }

    protected function applyWebhook(SpedizioneInpost $record, array $data): void
    {
        $service = new InpostService;
        $response = $record->response ?? [];
        $events = $response['webhookEvents'] ?? [];
        $events[] = [
            'received_at' => now()->toIso8601String(),
            'payload' => $data,
        ];

        $response['webhookEvents'] = array_slice($events, -20);
        $response['trackingResponse'] = array_merge(
            is_array($response['trackingResponse'] ?? null) ? $response['trackingResponse'] : [],
            $data
        );
        $response['trackingUpdatedAt'] = now()->toIso8601String();
        $record->response = $response;
        $record->tracking_number = $this->extractTrackingNumber($data) ?: $record->tracking_number;
        $record->shipment_uuid = $this->extractShipmentId($data) ?: $record->shipment_uuid;
        $record->esito = $service->extractShipmentStatus($data) ?: $record->esito;
        $record->esito_testo = $service->extractStatusText($data) ?: $record->esito_testo ?: $record->esito;
        $record->saveQuietly();
    }

    protected function logWebhook(Request $request, array $data, ?SpedizioneInpost $record): ChiamataApi
    {
        $log = new ChiamataApi;
        $log->servizio = 'inpost_webhook';
        $log->url = $request->fullUrl();
        $log->method = 'post';
        $log->request = [
            'headers' => collect($request->headers->all())->only([
                'x-inpost-signature',
                'inpost-signature',
                'x-signature',
                'x-hub-signature-256',
            ])->all(),
            'payload' => $data,
        ];
        $log->response = ['matched' => (bool) $record];
        $log->status = $record ? 200 : 202;

        if ($record) {
            $log->service_id = $record->id;
            $log->service_type = SpedizioneInpost::class;
        }

        $log->save();

        return $log;
    }

    protected function extractTrackingNumber(array $data): ?string
    {
        $paths = [
            'trackingNumber',
            'tracking_number',
            'parcel.trackingNumber',
            'shipment.trackingNumber',
            'shipment.tracking.number',
            'payload.trackingNumber',
            'payload.shipment.trackingNumber',
        ];

        foreach ($paths as $path) {
            $value = data_get($data, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    protected function extractShipmentId(array $data): ?string
    {
        $paths = [
            'shipmentUuid',
            'shipment_uuid',
            'shipmentId',
            'shipment_id',
            'id',
            'shipment.id',
            'shipment.uuid',
            'payload.shipmentUuid',
            'payload.shipment.id',
        ];

        foreach ($paths as $path) {
            $value = data_get($data, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}

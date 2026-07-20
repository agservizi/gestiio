<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LockerPackageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'recipientName' => $this->recipient_name,
            'recipientEmail' => $this->recipient_email,
            'recipientPhone' => $this->recipient_phone,
            'senderName' => $this->sender_name,
            'senderPhone' => $this->sender_phone,
            'carrier' => $this->carrier,
            'trackingCode' => $this->tracking_code,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'expectedPickupDate' => $this->expected_pickup_date?->toDateString(),
            'receivedAt' => $this->received_at?->toIso8601String(),
            'deliveredAt' => $this->delivered_at?->toIso8601String(),
            'dailyRate' => (float) $this->daily_rate,
            'totalAmount' => $this->total_amount !== null ? (float) $this->total_amount : null,
            'paymentMethod' => $this->payment_method,
            'qrToken' => $this->qr_token,
            'pickupUrl' => $this->when(
                $this->qr_token,
                fn () => $this->pickupUrl()
            ),
            'source' => $this->source,
            'notes' => $this->notes,
            'stationId' => $this->station_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

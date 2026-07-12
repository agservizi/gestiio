<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LuggageDepositResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'customerName' => $this->customer_name,
            'customerEmail' => $this->customer_email,
            'customerPhone' => $this->customer_phone,
            'bagCount' => $this->bag_count,
            'bagTags' => $this->bag_tags ?? [],
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'bookingDate' => $this->booking_date?->toDateString(),
            'expectedCheckIn' => $this->expected_check_in?->toIso8601String(),
            'expectedCheckOut' => $this->expected_check_out?->toIso8601String(),
            'checkedInAt' => $this->checked_in_at?->toIso8601String(),
            'checkedOutAt' => $this->checked_out_at?->toIso8601String(),
            'dailyRate' => (float) $this->daily_rate,
            'totalAmount' => $this->total_amount !== null ? (float) $this->total_amount : null,
            'paymentMethod' => $this->payment_method,
            'qrToken' => $this->qr_token,
            'verifyUrl' => $this->when(
                $this->qr_token,
                fn () => $this->verifyUrl()
            ),
            'source' => $this->source,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

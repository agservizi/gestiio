<?php

namespace App\Http\Requests;

use App\Models\LuggageDeposit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLuggageSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSettings', LuggageDeposit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'min_days' => ['nullable', 'integer', 'min:1'],
            'max_bags_per_booking' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'luggage_online_booking_enabled' => ['nullable', 'boolean'],
            'luggage_notify_staff' => ['nullable', 'boolean'],
            'luggage_notify_customer_receipt' => ['nullable', 'boolean'],
            'luggage_notify_customer_pickup_qr' => ['nullable', 'boolean'],
            'luggage_staff_notification_email' => ['nullable', 'email', 'max:255'],
            'luggage_booking_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'luggage_online_booking_enabled',
            'luggage_notify_staff',
            'luggage_notify_customer_receipt',
            'luggage_notify_customer_pickup_qr',
        ] as $checkbox) {
            if (! $this->has($checkbox)) {
                $this->merge([$checkbox => '0']);
            }
        }
    }
}

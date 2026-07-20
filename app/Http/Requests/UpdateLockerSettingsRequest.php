<?php

namespace App\Http\Requests;

use App\Models\LockerPackage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLockerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSettings', LockerPackage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'min_days' => ['nullable', 'integer', 'min:1'],
            'max_packages_per_booking' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'locker_online_intake_enabled' => ['nullable', 'boolean'],
            'locker_notify_staff' => ['nullable', 'boolean'],
            'locker_staff_notification_email' => ['nullable', 'email', 'max:255'],
            'locker_booking_instructions' => ['nullable', 'string', 'max:5000'],
            'locker_agent_monthly_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['locker_online_intake_enabled', 'locker_notify_staff'] as $checkbox) {
            if (! $this->has($checkbox)) {
                $this->merge([$checkbox => '0']);
            }
        }
    }
}

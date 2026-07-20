<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLockerBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipientName' => ['required', 'string', 'max:255'],
            'recipientEmail' => ['nullable', 'email', 'max:255'],
            'recipientPhone' => ['nullable', 'string', 'max:50'],
            'senderName' => ['nullable', 'string', 'max:255'],
            'senderPhone' => ['nullable', 'string', 'max:50'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'trackingCode' => ['nullable', 'string', 'max:100'],
            'expectedPickupDate' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function payload(): array
    {
        return [
            'recipient_name' => $this->input('recipientName'),
            'recipient_email' => $this->input('recipientEmail'),
            'recipient_phone' => $this->input('recipientPhone'),
            'sender_name' => $this->input('senderName'),
            'sender_phone' => $this->input('senderPhone'),
            'carrier' => $this->input('carrier'),
            'tracking_code' => $this->input('trackingCode'),
            'expected_pickup_date' => $this->input('expectedPickupDate'),
            'notes' => $this->input('notes'),
        ];
    }
}

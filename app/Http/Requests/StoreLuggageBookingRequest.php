<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLuggageBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
            'bagCount' => ['nullable', 'integer', 'min:1', 'max:100'],
            'bookingDate' => ['required', 'date', 'after_or_equal:today'],
            'expectedCheckIn' => ['nullable', 'date'],
            'expectedCheckOut' => ['nullable', 'date', 'after_or_equal:expectedCheckIn'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function payload(): array
    {
        return [
            'customer_name' => $this->input('customerName'),
            'customer_email' => $this->input('customerEmail'),
            'customer_phone' => $this->input('customerPhone'),
            'bag_count' => $this->input('bagCount', 1),
            'booking_date' => $this->input('bookingDate'),
            'expected_check_in' => $this->input('expectedCheckIn'),
            'expected_check_out' => $this->input('expectedCheckOut'),
            'notes' => $this->input('notes'),
        ];
    }
}

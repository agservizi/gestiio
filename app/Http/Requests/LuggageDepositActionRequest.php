<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LuggageDepositActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['check-in', 'check-out', 'cancel', 'no-show', 'delete'])],
            'bagTags' => ['nullable', 'array'],
            'bagTags.*' => ['string', 'max:50'],
            'paymentMethod' => ['nullable', 'string', 'max:50'],
        ];
    }
}

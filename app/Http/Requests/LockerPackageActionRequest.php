<?php

namespace App\Http\Requests;

use App\Models\LockerPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LockerPackageActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['intake', 'deliver', 'cancel', 'no-show', 'delete'])],
            'photo' => ['nullable', 'image', 'max:8192'],
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'signerName' => ['nullable', 'string', 'max:255'],
        ];
    }
}

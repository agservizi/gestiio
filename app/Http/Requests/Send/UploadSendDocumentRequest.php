<?php

namespace App\Http\Requests\Send;

use App\Enums\SendDocumentCategory;
use App\Models\SendRequest;
use App\Policies\SendRequestPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UploadSendDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $send = $this->route('send');

        return $send instanceof SendRequest
            && $this->user()?->can('uploadOperatorDocument', $send);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.((int) config('send.max_upload_kb', 20480))],
            'category' => ['required', Rule::in(array_column(SendDocumentCategory::cases(), 'value'))],
            'visibility' => ['nullable', 'in:operator,supervisor,citizen_receipt'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $category = (string) $this->input('category');
            $visibility = (string) ($this->input('visibility') ?: 'operator');

            if (! app(SendRequestPolicy::class)->canUploadOperatorCategory($user, $category, $visibility)) {
                $validator->errors()->add('category', 'Categoria o visibilità non consentita per il tuo ruolo.');
            }
        });
    }
}

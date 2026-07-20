<?php

namespace App\Http\Requests\Send;

use App\Enums\SendApplicantType;
use App\Enums\SendPriority;
use App\Rules\CodiceFiscaleRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSendRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $send = $this->route('send');
        if ($send instanceof \App\Models\SendRequest) {
            return $user->can('update', $send);
        }

        return $user->can('create', \App\Models\SendRequest::class);
    }

    public function rules(): array
    {
        return [
            'applicant_type' => ['required', Rule::in(array_column(SendApplicantType::cases(), 'value'))],
            'priority' => ['nullable', Rule::in(array_column(SendPriority::cases(), 'value'))],
            'send_notice_identifier' => ['nullable', 'string', 'max:120'],
            'iun' => ['nullable', 'string', 'max:120'],
            'sender_entity' => ['nullable', 'string', 'max:255'],
            'notice_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notice_pages' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'communication_type' => ['nullable', 'string', 'max:120'],
            'initial_notes' => ['nullable', 'string', 'max:5000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string'],
            'consents' => ['nullable', 'array'],
            'upload_uid' => ['nullable', 'string', 'max:40'],
            'subjects' => ['nullable', 'array'],
            'subjects.destinatario.first_name' => ['nullable', 'string', 'max:100'],
            'subjects.destinatario.last_name' => ['nullable', 'string', 'max:100'],
            'subjects.destinatario.tax_code' => ['nullable', 'string', 'max:32', new CodiceFiscaleRule],
            'subjects.delegato.first_name' => ['nullable', 'string', 'max:100'],
            'subjects.delegato.last_name' => ['nullable', 'string', 'max:100'],
            'subjects.delegato.tax_code' => ['nullable', 'string', 'max:32', new CodiceFiscaleRule],
            'subjects.impresa.business_name' => ['nullable', 'string', 'max:255'],
            'subjects.impresa.vat_number' => ['nullable', 'string', 'max:20'],
            'subjects.impresa.tax_code' => ['nullable', 'string', 'max:32'],
            'subjects.rappresentante.first_name' => ['nullable', 'string', 'max:100'],
            'subjects.rappresentante.last_name' => ['nullable', 'string', 'max:100'],
            'subjects.rappresentante.tax_code' => ['nullable', 'string', 'max:32', new CodiceFiscaleRule],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}

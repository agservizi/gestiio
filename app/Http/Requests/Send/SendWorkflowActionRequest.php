<?php

namespace App\Http\Requests\Send;

use Illuminate\Foundation\Http\FormRequest;

class SendWorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policy checked in controller
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'integration_due_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:internal,operator,citizen'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'recipient_type' => ['nullable', 'string', 'max:40'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['nullable', 'string', 'max:60'],
            'identification_type' => ['nullable', 'string', 'max:80'],
            'document_verified' => ['nullable', 'string', 'max:120'],
            'delivered_at' => ['nullable', 'date'],
            'documents_summary' => ['nullable', 'string', 'max:5000'],
            'print_done' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Send;

use Illuminate\Foundation\Http\FormRequest;

class UploadSendClientDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.((int) config('send.max_upload_kb', 20480))],
        ];
    }
}

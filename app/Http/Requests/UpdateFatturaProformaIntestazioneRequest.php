<?php

namespace App\Http\Requests;

use App\Models\FatturaProforma;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFatturaProformaIntestazioneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fattura = FatturaProforma::find($this->route('id'));

        return $fattura && $this->user()?->can('updateIntestazione', $fattura);
    }

    public function rules(): array
    {
        return [
            'denominazione' => ['required', 'string', 'max:255'],
            'codice_fiscale' => ['nullable', 'string', 'max:32'],
            'indirizzo' => ['nullable', 'string', 'max:255'],
            'citta' => ['nullable', 'string', 'max:120'],
            'cap' => ['nullable', 'string', 'max:10'],
            'nazione' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'denominazione.required' => 'La denominazione è obbligatoria.',
        ];
    }
}

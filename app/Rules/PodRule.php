<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PodRule implements Rule
{
    protected $message = 'Il POD non è valido.';

    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        $pod = strtoupper(trim((string) $value));
        $pod = preg_replace('/\s+/', '', $pod);

        // Formato POD Italia: IT + 3 cifre + E + 8 caratteri alfanumerici (14 char totali).
        if (! preg_match('/^IT\d{3}E[A-Z0-9]{8}$/', $pod)) {
            $this->message = 'Il POD deve avere formato IT001E12345678.';

            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->message;
    }
}

<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PdrRule implements Rule
{
    protected $message = 'Il PDR non è valido.';

    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        $pdr = trim((string) $value);
        $pdr = preg_replace('/\s+/', '', $pdr);

        // Formato PDR Italia: 14 cifre numeriche.
        if (!preg_match('/^\d{14}$/', $pdr)) {
            $this->message = 'Il PDR deve contenere esattamente 14 cifre.';
            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->message;
    }
}


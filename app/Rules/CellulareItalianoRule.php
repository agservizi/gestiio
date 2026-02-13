<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class CellulareItalianoRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        if (!$value) {
            return true;
        }

        //Tolgo +39
        $valore = str_replace('+39', '', $value);
        if (!Str::of($valore)->startsWith('3')) {
            return false;
        }

        $lunghezza = Str::of($valore)->length();
        if ($lunghezza < 9 || $lunghezza > 10) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Il numero di cellulare non è corretto';
    }
}

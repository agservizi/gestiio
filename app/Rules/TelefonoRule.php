<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TelefonoRule implements Rule
{
    protected $message;

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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_numeric(str_replace(' ', '', $value))) {
            return true;
        } else {
            $this->message = $attribute.' deve essere un numero.';

            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}

<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CodiceDestinatarioRule implements Rule
{
    protected $message;
    protected $nazione;

    /**
     * Create a new rule instance.
     *
     * @param string $nazione
     * @param null $tipoCliente
     */
    public function __construct($nazione)
    {
        $this->nazione = strtoupper($nazione);
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
        if ($this->nazione != 'IT') {
            $upper = strtoupper($value);
            if ($upper !== 'XXXXXXX' && $upper !== '0000000') {
                $this->message = "Il codice destinatario per gli stati esteri deve essere XXXXXXX o 0000000";
                return false;
            }
            return true;
        }

        return $this->controlloLunghezza($value);
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


    protected function controlloLunghezza($attribute)
    {
        $lunghezza = strlen($attribute);

        if ($attribute === '000000') {
            $this->message = "Il codice destinatario è errato. La lunghezza deve essere di 7 caratteri";
            return false;

        }

        if (!in_array($lunghezza, [6, 7])) {
            $this->message = "La lunghezza del codice destinatario deve essere di 6 o 7 caratteri";
            return false;
        }
        return true;

    }

}

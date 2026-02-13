<?php

namespace App\Rules;

use App\Models\ClienteFornitore;
use Illuminate\Contracts\Validation\Rule;

class PartitaIvaRule implements Rule
{
    protected $message;
    protected $nazione;
    protected $tipoCliente;
    protected $clientefornitoreId;

    /**
     * Create a new rule instance.
     *
     * @param string $nazione
     * @param null $tipoCliente
     * @param null $clientefornitoreId
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
        //




        $pi = $value;

        if ($pi === '') return '';
        if (strlen($pi) != 11) {
            $this->message = "La lunghezza della partita IVA non è "
                . "corretta: la partita IVA dovrebbe essere lunga "
                . "esattamente 11 caratteri. ";
            return false;

        }
        if (preg_match("/^[0-9]+\$/", $pi) != 1) {
            $this->message = "La partita IVA contiene dei caratteri non ammessi: "
                . "la partita IVA dovrebbe contenere solo cifre. ";
            return false;
        }
        $s = 0;
        for ($i = 0; $i <= 9; $i += 2)
            $s += ord($pi[$i]) - ord('0');
        for ($i = 1; $i <= 9; $i += 2) {
            $c = 2 * (ord($pi[$i]) - ord('0'));
            if ($c > 9) $c = $c - 9;
            $s += $c;
        }
        if ((10 - $s % 10) % 10 != ord($pi[10]) - ord('0')) {
            $this->message = "La partita IVA non è valida: "
                . "il codice di controllo non corrisponde.";
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
        return $this->message;
    }
}

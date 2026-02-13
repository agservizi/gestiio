<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CodiceFiscaleRule implements Rule
{

    protected $message;

    /**
     * Create a new rule instance.
     *
     * @param string $nazione
     * @param null $tipoCliente
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

        if (strlen($value) == 11) {
            return $this->partitaIva($value);
        }

        $cf = $value;

        if ($cf === '') return '';
        if (strlen($cf) != 16) {

            $this->message = "La lunghezza del codice fiscale non  è "
                . "corretta: il codice fiscale dovrebbe essere lungo "
                . "esattamente 16 caratteri.";
            return false;
        }
        $cf = strtoupper($cf);
        if (preg_match("/^[A-Z0-9]+\$/", $cf) != 1) {

            $this->message = "Il codice fiscale contiene dei caratteri non validi: "
                . "i soli caratteri validi sono le lettere e le cifre.";
            return false;
        }
        $s = 0;
        for ($i = 1; $i <= 13; $i += 2) {
            $c = $cf[$i];
            if (strcmp($c, "0") >= 0 and strcmp($c, "9") <= 0)
                $s += ord($c) - ord('0');
            else
                $s += ord($c) - ord('A');
        }
        for ($i = 0; $i <= 14; $i += 2) {
            $c = $cf[$i];
            switch ($c) {
                case '0':
                    $s += 1;
                    break;
                case '1':
                    $s += 0;
                    break;
                case '2':
                    $s += 5;
                    break;
                case '3':
                    $s += 7;
                    break;
                case '4':
                    $s += 9;
                    break;
                case '5':
                    $s += 13;
                    break;
                case '6':
                    $s += 15;
                    break;
                case '7':
                    $s += 17;
                    break;
                case '8':
                    $s += 19;
                    break;
                case '9':
                    $s += 21;
                    break;
                case 'A':
                    $s += 1;
                    break;
                case 'B':
                    $s += 0;
                    break;
                case 'C':
                    $s += 5;
                    break;
                case 'D':
                    $s += 7;
                    break;
                case 'E':
                    $s += 9;
                    break;
                case 'F':
                    $s += 13;
                    break;
                case 'G':
                    $s += 15;
                    break;
                case 'H':
                    $s += 17;
                    break;
                case 'I':
                    $s += 19;
                    break;
                case 'J':
                    $s += 21;
                    break;
                case 'K':
                    $s += 2;
                    break;
                case 'L':
                    $s += 4;
                    break;
                case 'M':
                    $s += 18;
                    break;
                case 'N':
                    $s += 20;
                    break;
                case 'O':
                    $s += 11;
                    break;
                case 'P':
                    $s += 3;
                    break;
                case 'Q':
                    $s += 6;
                    break;
                case 'R':
                    $s += 8;
                    break;
                case 'S':
                    $s += 12;
                    break;
                case 'T':
                    $s += 14;
                    break;
                case 'U':
                    $s += 16;
                    break;
                case 'V':
                    $s += 10;
                    break;
                case 'W':
                    $s += 22;
                    break;
                case 'X':
                    $s += 25;
                    break;
                case 'Y':
                    $s += 24;
                    break;
                case 'Z':
                    $s += 23;
                    break;
                /*. missing_default: .*/
            }
        }
        if (chr($s % 26 + ord('A')) != $cf[15]) {
            $this->message = "Il codice fiscale non  è corretto: "
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


    protected function partitaIva($pi)
    {

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

}

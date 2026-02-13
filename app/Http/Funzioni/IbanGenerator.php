<?php

namespace App\Http\Funzioni;

use Illuminate\Support\Str;

class IbanGenerator
{


    /**
     * Constructor.
     *
     * @param string $abi
     * @param string $numeroConto
     * @param string $locale
     */
    public function __construct(protected $abi, protected $cab, protected $numeroConto, protected $locale = 'IT')
    {
        $this->abi = Str::padLeft($abi, 5, '0');
        $this->cab = Str::padLeft($cab, 5, '0');
        $this->numeroConto = Str::padLeft($numeroConto, 12, '0');
    }

    /**
     * Generate the IBAN Code.
     *
     * @param string $bankCode
     * @param string $bankAccountNr
     * @param string $locale
     *
     * @return string
     */
    public function generate()
    {
        $bban = $this->Calcola_BBAN($this->abi, $this->cab, $this->numeroConto);
        $iban = $this->Calcola_IBAN($bban, $this->locale);


        return $iban;
    }

    protected function Calcola_BBAN($abi, $cab, $ccc)
    {
        $contributi =
            array(1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2,
                4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10,
                22, 25, 24, 23);
        $arr_bban = str_split($abi . $cab . $ccc);
        $pari = false;
        $somma = 0;
        foreach ($arr_bban as $char) {
            $char2 = is_numeric($char) ? $char : ord($char) - 65;
            $somma += $pari ? $char2 : $contributi[$char2];
            $pari = !$pari;
        }
        $cin = chr(($somma % 26) + 65);
        return $cin . $abi . $cab . $ccc;
    }

    protected function Calcola_IBAN($bban, $cdnaz)
    {
        if ($cdnaz == '') $cdnaz = 'IT';
        $arr_bban = str_split($bban . $cdnaz . '00');
        $digits = '';
        foreach ($arr_bban as $char) {
            $digits .= is_numeric($char) ? $char : ord($char) - 55;
            if (strlen($digits) > 8) $digits = $digits % 97;
        }
        $check = (98 - ($digits % 97)) % 97;
        return $cdnaz . sprintf('%02s', $check) . $bban;
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoEnergiaIllumia extends Model
{

    protected $table = "prodotto_energia_illumia";

    protected $primaryKey = 'contratto_energia_id';


    public const NOME_SINGOLARE = "prodotto energia illumia";
    public const NOME_PLURALE = "prodotto energia illumia";

    protected $casts = [
        'fornitura_richiesta' => 'array',
        'fasce_reperibilita' => 'array',
        'data_rilascio' => 'datetime',
        'data_scadenza' => 'datetime',
    ];

    public const FORNITURA_RICHIESTA = ['luce' => 'Energia elettrica', 'gas' => 'Gas Naturale'];
    public const FASCE_REPERIBILITA = ['9-13' => 'Lun. - Ven. 09.00 - 13.00', '14-18' => 'Lun. - Ven. 14.00 - 18.00'];
    public const TIPOLOGIA_USO_GAS = ['acqua-calda' => 'Acqua calda e/o cottura', 'riscaldamento' => 'Riscaldamento'];
    public const MODALITA_PAGAMENTO_FATTURA = ['bollettino-postale' => 'Bollettino Postale', 'conto-corrente' => 'Addebito su Conto Corrente Bancario'];
    public const MODALITA_SPEDIZIONE_FATTURA = ['Cartacea' => 'Cartacea', 'Email' => 'Email'];
    public const VIRTU_TITOLO = ['Proprietà' => 'Proprietà', 'Locazione' => 'Locazione', 'Comodato' => 'Comodato', 'Altro' => 'Altro'];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function comune()
    {
        return $this->hasOne(Comune::class, 'id', 'citta');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    public function fornituraRichiestaArray()
    {
        $str = '';
        foreach ($this->fornitura_richiesta as $value) {
            if ($str) $str .= ', ';
            $str .= self::FORNITURA_RICHIESTA[$value];
        }
        return $str;
    }

    public function fasceReperibilitaArray()
    {
        $str = '';
        foreach ($this->fasce_reperibilita as $value) {
            if ($str) $str .= ', ';
            $str .= self::FASCE_REPERIBILITA[$value];
        }
        return $str;
    }

    public function tipologiaUsoGasArray()
    {
        if ($this->tipologia_uso_gas) {
            $str = '';
            foreach ($this->tipologia_uso_gas as $value) {
                if ($str) $str .= ', ';
                $str .= self::TIPOLOGIA_USO_GAS[$value];
            }
            return $str;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

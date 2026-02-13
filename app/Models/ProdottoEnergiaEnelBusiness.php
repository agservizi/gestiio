<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoEnergiaEnelBusiness extends Model
{
    use HasFactory;

    protected $table = "prodotto_energia_enelbusiness";

    protected $primaryKey = 'contratto_energia_id';


    public const NOME_SINGOLARE = "prodottoenergiaenelbusiness";
    public const NOME_PLURALE = "";

    protected $casts=[
        'data_fine_validita' => 'date',
        'data_inizio_validita' => 'date',
        'data_scadenza' => 'date',
        'data_rilascio' => 'date',
    ];

    public const MODALITA_PAGAMENTO = [
        'bollettino' => 'Bollettino postale',
        'addebito_cc' => 'Addebito su C/C SDD',
        'addebito_sepa' => 'Addebito su banca estera SEPA',
    ];
    public const INVIO_FATTURA = [
        'doppio' => 'Doppio',
        'singolo' => 'Singolo',
    ];

    public const PROFILI_CONSUMO = [
        'riscaldamento' => 'riscaldamento',
        'produzione_riscaldamento' => 'Produzione e riscaldamento',
        'produzione' => 'Produzione',
        'cottura_acqua' => 'Cottura e/o acqua calda'
    ];

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

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoEnergiaEnelConsumer extends Model
{

    protected $table = "prodotto_energia_enelconsumer";
    protected $primaryKey = 'contratto_energia_id';

    public const NOME_SINGOLARE = "prodottoenelconsumer";
    public const NOME_PLURALE = "";

    public const MODALITA_PAGAMENTO = [
        'bollettino' => 'Bollettino postale',
        'addebito_cc' => 'Addebito su C/C SDD',
        'addebito_sepa' => 'Addebito su banca estera SEPA',
    ];
    public const INVIO_FATTURA = [
        'doppio' => 'Doppio',
        'singolo' => 'Singolo',
    ];

    protected $casts = [
        'data_rilascio' => 'date',
        'data_scadenza' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function comuneIndirizzo(){
        $this->hasOne(Comune::class,'id','citta');
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

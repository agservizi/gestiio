<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoVodafoneFissa extends Model
{
    use HasFactory;
    protected $table="prodotto_vodafone_fissa";

    protected $primaryKey = 'contratto_id';

    public const NOME_SINGOLARE = "prodottovodafonefissa";
    public const NOME_PLURALE = "prodottovodafonefisse";


    public const OFFERTE = [
        'con_chiamate' => 'Con chiamate',
        'senza_chiamate' => 'Senza chiamate',
    ];
    public const METODO_PAGAMENTO = [
        'iban' => 'Iban',
        'bollettino' => 'Bollettino postale',
    ];
    public const TECNOLOGIE = [
        'FTTH' => 'FTTH',
        'FTTC' => 'FTTC',
        'FWA' => 'FWA',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

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

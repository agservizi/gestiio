<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoSkyWifi extends Model
{

    protected $table = "prodotto_skywifi";

    protected $primaryKey = 'contratto_id';

    public const NOME_SINGOLARE = "prodottoskytv";
    public const NOME_PLURALE = "";

    protected $casts = [
        'pacchetti_voce' => 'array',
    ];

    public const PROFILO = [
        'sky_open',
        'sky_smart'
    ];
    public const TIPOLOGIA_CLIENTE = [
        'persona_fisica' => 'Persona fisica',
        'societa' => 'Società',
        'ditta_individuale' => 'Ditta individuale/Lavoratore autonomo',
        'associazione' => 'Associazione no-profit',
    ];

    public const PACCHETTI_VOCE = [
        'internet_senza_voce' => 'Internet senza voce',
        'chiamate_consumo' => 'Chiamate a consumo',
        'voce_unlimited' => 'Voce unlimited',
        'voce_estero' => 'Voce_estero',
    ];

    public const OFFERTE = [
        'fibra100' => 'Fibra100%',
        'super_internet' => 'Super Internet',
    ];

    public const METODO_PAGAMENTO_INTERNET = [
        'carta_credito' => 'Carta di credito',
        'sdd' => 'SEPA',
    ];

    public const LINEA_TELEFONICA = [
        'richiesta_nuova_linea' => 'Richiesta nuova linea telefonica',
        'mantenimento_numero' => 'Linea attiva con mantenimento attuale numero telefonico <i class="fas fa-exclamation-circle ms-1 fs-6 " data-bs-toggle="tooltip" title="Attenzione, ricorda
al cliente che non sarà necessario mandare disdetta al
vecchio operatore"></i>',
    ];
    public const FREQUENZA_PAGAMENTO_TV = [
        'unica_soluzione',
        'rate_mensili',
    ];

    public const CARTA_DI_CREDITO = [
        'american_express',
        'diners',
        'master_card',
        'visa',
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

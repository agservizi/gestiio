<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoTimWifi extends Model
{
    protected $table="prodotto_tim_wifi";

    protected $primaryKey = 'contratto_id';

    public const NOME_SINGOLARE = "prodottotimwifi";
    public const NOME_PLURALE = "";

    protected $casts=[
        'offerta_scelta'=>'array',
        'opzione_inclusa'=>'array',
        'firmatario_data_emissione'=>'date',
        'firmatario_data_scadenza'=>'date',
    ];

    public const TIPO_LINEA = [
        'ATTIVAZIONE NUOVA LINEA FISSA' => 'ATTIVAZIONE NUOVA LINEA FISSA',
        'ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE' => 'ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE',
    ];
    public const OFFERTA = [
        'TIM WiFi Power FIBRA' => 'TIM WiFi Power FIBRA',
        'TIM WiFi Power MEGA FTTCF' => 'TIM WiFi Power MEGA FTTCF',
        'TIM WiFi Power MEGA FTTE' => 'TIM WiFi Power MEGA FTTE',
    ];

    public const OPZIONI = [
        'MODEM' => 'MODEM',
        'VOCE' => 'VOCE',
        'TIM NAVIGAZIONE SICURA' => 'TIM NAVIGAZIONE SICURA',
        'PREMIUM FLEXY' => 'PREMIUM FLEXY',
        'SMART HOME' => 'SMART HOME',
    ];

    public const MODEM = [

        'TIM HUB+ in 48 rate' => 'TIM HUB+ in 48 rate',
        'TIM HUB+ in 24 rate' => 'TIM HUB+ in 24 rate',
        'TIM HUB+ in 12 rate' => 'TIM HUB+ in 12 rate',
        'TIM HUB+ in unica soluzione' => 'TIM HUB+ in unica soluzione',
    ];


    public const OFFERTA_SCELTA=[
        'TIMVISION con Disney+'=>'TIMVISION con Disney+',
        'TIMVISION con Netflix'=>'TIMVISION con Netflix',
        'TIMVISION Intrattenimento'=>'TIMVISION Intrattenimento',
        'Opzione + 5G Power'=>'Opzione + 5G Power',
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

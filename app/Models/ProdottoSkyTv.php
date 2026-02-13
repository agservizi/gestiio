<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoSkyTv extends Model
{
    use HasFactory;

    protected $table = "prodotto_skytv";

    protected $primaryKey = 'contratto_id';

    public const NOME_SINGOLARE = "prodottoskytv";
    public const NOME_PLURALE = "";

    protected $casts = [
        'pacchetti_sky' => 'array',
        'servizi_opzionali' => 'array',
        'offerte_sky' => 'array',
        'canali_opzionali' => 'array',
        'servizio_decoder' => 'array',
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

    public const PACCHETTI_SKY_TV = [
        'kids' => 'Sky Kids',
        'cinema' => 'Sky Cinema',
        'sport' => 'Sky Sport',
        'calcio' => 'Sky Calcio',
    ];

    public const SERVIZI_OPZIONALI = [
        'sky_ultra_hd',
        'sky_go',
        'sky_multiscreen'
    ];
    public const OFFERTE_SKY = [
        'base' => 'Netflix base',
        'standard' => 'Netflix Standard',
        'premium' => 'Netflix premium',
    ];
    public const TECNOLOGIA = [
        'sky_q' => 'Sky Q internet',
        'sky_q_black' => 'Sky Q Black satellite',
        'sky_q_platinum' => 'Sky Q Platinum satellite',
    ];
    public const METODO_PAGAMENTO_TV = [
        'carta_credito' => 'Carta di credito',
        'sdd' => 'SEPA',
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

    public function pacchettiSky()
    {
        if (!$this->pacchetti_sky) {
            return null;
        }
        $arr = [];
        foreach ($this->pacchetti_sky as $value) {
            $arr[] = self::PACCHETTI_SKY_TV[$value];
        }
        return implode(', ', $arr);
    }

    public function serviziOpzionali()
    {
        if (!$this->servizi_opzionali) {
            return null;
        }

        return ucwords(str_replace('_', ' ', implode(', ', $this->servizi_opzionali)));
    }
}

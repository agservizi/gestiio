<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoSkyGlass extends Model
{
    use HasFactory;

    protected $table = "prodotto_skyglass";

    protected $primaryKey = 'contratto_id';


    public const NOME_SINGOLARE = "prodottoskyglass";
    public const NOME_PLURALE = "";


    protected $casts = [
        'servizi_opzionali' => 'json',
        'pacchetti_sky' => 'json',
        'pacchetti_netflix' => 'json',
    ];

    public const DIMENSIONI = [
        '43' => '43 pollici',
        '55' => '55 pollici',
        '65' => '65 pollici',
    ];

    public const COLORI_GLASS = [
        'bianco' => 'bianco',
        'blu' => 'blu',
        'nero' => 'nero',
        'verde' => 'verde',
        'rosa' => 'rosa',
    ];
    public const COLORI_COVER = [
        'no' => 'No',
        'bianco' => 'bianco',
        'blu' => 'blu',
        'nero' => 'nero',
        'verde' => 'verde',
        'rosa' => 'rosa',
    ];
    public const PACCHETTI_SKY_TV = [
        'kids' => 'Sky Kids',
        'cinema' => 'Sky Cinema',
        'sport' => 'Sky Sport',
        'calcio' => 'Sky Calcio',
    ];
    public const PACCHETTI_NETFLIX = [
        'base' => 'Netflix base',
        'standard' => 'Netflix Standard',
        'premium' => 'Netflix premium',
    ];
    public const SERVIZI_OPZIONALI = [
        'ultra_hd' => 'Sky Ultra HD',
        'glass_multiscreen' => 'Sky Glass Multiscreen',
    ];
    public const FREQUENZA_PAGAMENTO = [
        'unica' => 'Unica soluzione',
        'acconto+24_mesi' => 'Acconto + Rateale 24 mesi',
        'acconto+48_mesi' => 'Acconto + Rateale 48 mesi',
    ];
    public const TIPOLOGIA_CLIENTE = [
        'persona_fisica' => 'Persona fisica',
        'ditta_individuale' => 'Ditta individuale/Lavoratore autonomo',
        'azienda' => 'Azienda/Società/Associazione no-profit',
    ];
    public const METODO_PAGAMENTO = [
        'carta_credito' => 'Carta di credito',
        'iban' => 'IBAN',
    ];

    public const METODO_PAGAMENTO_TV = [
        'carta_credito' => 'Carta di credito',
        'sdd' => 'SDD',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function contratto()
    {
        return $this->hasOne(ContrattoTelefonia::class, 'id', 'contratto_id');
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoWindtre extends Model
{
    protected $table = 'prodotto_windtre';

    protected $primaryKey = 'contratto_id';

    protected $casts = ['opzioni' => 'array'];

    public const OPZIONI=[
        'CallYourCountryHome' => 'Call Your Country Home',
        'ChiamateIllimitate' => 'Chiamate illimitate',
        'ConvergenzaSuperFibra' => 'Convergenza Super Fibra',
        'DNSDinamico' => 'DNS Dinamico',
        'InVista' => 'In Vista',
        'LimitedEdition' => 'Limited Edition',
        'PiùSicuriCasaEUfficio' => 'Più Sicuri Casa&Ufficio',
    ];


    public function opzioniBlade()
    {
        if (!$this->opzioni) {
            return null;
        }
        $arr = [];
        foreach ($this->opzioni as $value) {
            $arr[] = self::OPZIONI[$value];
        }
        return implode(', ', $arr);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListinoBrt extends Model
{
    use HasFactory;

    protected $table = "brt_listino";

    public const NOME_SINGOLARE = "listinobrt";
    public const NOME_PLURALE = "";

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


    public static function trovaTariffa($peso)
    {
        $record = self::where('da_peso', '<=', $peso)->where('a_peso', '>=', $peso)->first();
        if ($record) {

            return $record;
        }

        $record = self::where('da_peso', '<=', $peso)->whereNull('a_peso')->first();

        return $record;
    }

    public function calcolaPrezzo($peso, $pudo)
    {
        $tariffa = $this->prendiTariffa($pudo);
        if ($this->al_kg) {
            return $tariffa * $peso;
        }
        return $tariffa;
    }

    protected function prendiTariffa($pudo)
    {

        if ($pudo && $this->brt_fermopoint) {
            return $this->brt_fermopoint;
        } else {
            return $this->home_delivery;
        }
    }
}

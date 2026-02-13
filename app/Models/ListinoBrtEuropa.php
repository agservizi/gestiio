<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListinoBrtEuropa extends Model
{
    use HasFactory;

    protected $table = "brt_listino_europa";

    public const NOME_SINGOLARE = "listinobrteuropa";
    public const NOME_PLURALE = "listinobrteurope";

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


    public static function trovaTariffa($peso, $tipo)
    {
        $record = self::where('da_peso', '<=', $peso)->where('a_peso', '>=', $peso)->where('tipo', $tipo)->first();
        if ($record) {
            return $record;
        }

        return null;
    }

    public function calcolaPrezzo( $gruppo)
    {
        $tariffa = $this->prendiTariffa($gruppo);
        return $tariffa;
    }

    protected function prendiTariffa($gruppo)
    {
        $campo = 'gruppo_' . $gruppo;
        return $this->$campo;
    }
}

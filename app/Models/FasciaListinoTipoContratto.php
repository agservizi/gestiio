<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FasciaListinoTipoContratto extends Model
{

    protected $table = "listini_fasce_contratti";

    public const NOME_SINGOLARE = "fascia listino";
    public const NOME_PLURALE = "fasce listino";

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

    public function descriviFascia()
    {
        return 'da ordini:' . $this->da_contratti . ' a_contratti:' . $this->a_contratti . ' importo:' . $this->importo_per_contratto . ' bonus:' . $this->importo_bonus;
    }

    public function calcolaGuadagno($numeroContratti)
    {
        $importoBase = $numeroContratti * $this->importo_per_contratto;
        $importoBonus = $numeroContratti * $this->importo_bonus;
        return $importoBase + $importoBonus;

    }
}

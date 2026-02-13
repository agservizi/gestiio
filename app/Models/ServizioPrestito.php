<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServizioPrestito extends Model
{
    use HasFactory;

    protected $table = "servizio_prestiti";
    protected $primaryKey='servizio_id';


    public const NOME_SINGOLARE = "servizioprestito";
    public const NOME_PLURALE = "servizioprestiti";

    public const DURATA_MESI = [36, 48, 60, 72, 84, 96, 108, 120];
    public const STATO_CIVILE = ['celibe/nubile', 'coniugato', 'separato', 'vedovo'];
    public const IMMOBILE_RESIDENZA = ['affitto' => 'Affitto', 'proprietà' => 'Proprietà'];
    public const LAVORO = ['dipendente' => 'Dipendente', 'autonomo' => 'Autonomo', 'pensionato' => 'Pensionato'];


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

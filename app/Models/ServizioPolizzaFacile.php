<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServizioPolizzaFacile extends Model
{
    use HasFactory;

    protected $table = "servizio_polizze_facile";
    protected $primaryKey = 'servizio_id';


    public const NOME_SINGOLARE = "servizio_polizze_facile";
    public const NOME_PLURALE = "servizio_polizze_facile";

    protected $casts = [
        'data_di_nascita' => 'datetime'
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

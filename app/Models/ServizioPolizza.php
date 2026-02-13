<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServizioPolizza extends Model
{
    use HasFactory;

    protected $table = "servizio_polizze";
    protected $primaryKey = 'servizio_id';


    public const NOME_SINGOLARE = "serviziopolizza";
    public const NOME_PLURALE = "serviziopolizze";

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

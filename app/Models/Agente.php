<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agente extends Model
{
    protected $table = 'agenti';

    public const NOME_SINGOLARE = 'agente';

    public const NOME_PLURALE = 'agenti';

    protected $fillable = ['user_id'];

    protected $casts = [
        'openapi_visure_token' => 'encrypted',
        'openapi_catasto_token' => 'encrypted',
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

    public function urlVisuraCamerale()
    {
        return $this->visura_camerale ? ('/storage'.$this->visura_camerale) : null;
    }
}

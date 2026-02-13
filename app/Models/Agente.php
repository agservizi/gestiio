<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agente extends Model
{
    protected $table="agenti";

    public const NOME_SINGOLARE = "agente";
    public const NOME_PLURALE = "agenti";

    protected $fillable=['user_id'];

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
        return $this->visura_camerale ? ('/storage' . $this->visura_camerale) : null;
    }
}

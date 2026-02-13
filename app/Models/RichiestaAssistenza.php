<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RichiestaAssistenza extends Model
{
    use HasFactory;
    protected $table="richieste_assistenza";

    public const NOME_SINGOLARE = "richiesta assistenza";
    public const NOME_PLURALE = "richieste assistenze";

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function cliente(): HasOne
    {
        return $this->hasOne(ClienteAssistenza::class,'id','cliente_id');
    }
    public function prodotto(): HasOne
    {
        return $this->hasOne(ProdottoAssistenza::class,'id','prodotto_assistenza_id');
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

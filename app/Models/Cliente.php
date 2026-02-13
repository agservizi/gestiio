<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = "clienti";

    public const NOME_SINGOLARE = "cliente";
    public const NOME_PLURALE = "clienti";

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function comune()
    {
        return $this->hasOne(Comune::class, 'id', 'citta');
    }

    public function utente()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
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

    public function nominativo()
    {
        return $this->cognome . ' ' . $this->nome;
    }

    public function denominazione()
    {
        return $this->ragione_sociale ?: ($this->cognome . ' ' . $this->nome);
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

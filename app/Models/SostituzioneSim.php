<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SostituzioneSim extends Model
{
    use HasFactory;
    protected $table="attivazioni_sim_sostituzioni";

    public const NOME_SINGOLARE = "sostituzione sim";
    public const NOME_PLURALE = "sostituzioni sim";

    const MOTIVAZIONI = [
        'problemi_tecnici' => 'Problemi tecnici',
        'forto_smarrimento' => 'Furto  o smarrimento'
    ];


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */
    public function agente()
    {
        return $this->hasOne(Agente::class, 'id', 'agente_id');
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

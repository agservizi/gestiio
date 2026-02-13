<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsitoSegnalazione extends Model
{
    use Sluggable;
    public $incrementing = false;
    protected $table="tab_esiti_segnalazioni";

    public const NOME_SINGOLARE = "esito segnalazione";
    public const NOME_PLURALE = "esiti segnalazione";

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

    public function labelStato()
    {
        return "<span class='badge' style='background-color: {$this->colore_hex}'>{$this->nome}</span>";
    }


    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
    public function sluggable(): array
    {
        return [
            'id' => [
                'source' => 'nome'
            ]
        ];
    }
}

<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsitoContrattoEnergia extends Model
{
    use HasFactory;
    use Sluggable;

    protected $table = "tab_esiti_contratti_energia";
    public $incrementing = false;


    public const NOME_SINGOLARE = "esito contratto energia";
    public const NOME_PLURALE = "esiti contratti energia";

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'id' => [
                'source' => 'nome'
            ]
        ];
    }

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

    public function labelEsito()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:' . ContrattoTelefonia::ESITI[$this->esito_finale] . ';"></span>';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

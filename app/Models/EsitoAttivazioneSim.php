<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsitoAttivazioneSim extends Model
{
    use Sluggable;

    protected $table="tab_esiti_attivazioni_sim";
    public $incrementing = false;

    public const NOME_SINGOLARE = "esito attivazione sim";
    public const NOME_PLURALE = "esiti attivazioni sim";


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

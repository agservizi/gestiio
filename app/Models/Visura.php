<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Visura extends Model
{
    use HasFactory;

    protected $table = "visure";

    public const NOME_SINGOLARE = "visura";
    public const NOME_PLURALE = "visure";

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7'
    ];

    protected $casts = [
        'data' => 'datetime'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            }
        });

    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente()
    {
        return $this->hasOne(User::class, 'id', 'agente_id');
    }

    public function allegati()
    {
        return $this->morphMany(AllegatoServizio::class, 'allegato')->where('per_cliente', 0);
    }

    public function allegatiPerCliente()
    {
        return $this->morphMany(AllegatoServizio::class, 'allegato')->where('per_cliente', 1);
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function esito()
    {
        return $this->hasOne(EsitoVisura::class, 'id', 'esito_id');
    }

    public function tipo()
    {
        return $this->hasOne(TipoVisura::class, 'id', 'tipo_visura_id');
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

    public function bulletEsitoFinale()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:' . self::ESITI[$this->esito_finale] . ';"></span>';
        }

    }

    public function nominativo()
    {
        return $this->ragione_sociale ?? ($this->cognome . ' ' . $this->nome);
    }


    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

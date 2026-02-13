<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttivazioneSim extends Model
{
    use HasFactory;

    protected $table = "attivazioni_sim";

    public const NOME_SINGOLARE = "attivazione sim";
    public const NOME_PLURALE = "attivazioni sim";

    protected $casts = [
        'data' => 'datetime',
        'data_scadenza' => 'datetime',
    ];

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7'
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
        return $this->hasMany(AllegatoAttivazioneSim::class, 'attivazioni_sim_id');
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function comune()
    {
        return $this->hasOne(Comune::class, 'id', 'citta');
    }


    public function esito()
    {
        return $this->hasOne(EsitoAttivazioneSim::class, 'id', 'esito_id');
    }

    public function gestore()
    {
        return $this->hasOne(GestoreAttivazioniSim::class, 'id', 'gestore_id');
    }

    public function offerta()
    {
        return $this->hasOne(OffertaSim::class, 'id', 'offerta_sim_id');
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

    public function labelPagato()
    {
        if ($this->pagato) return "<span class='badge badge-success' >Pagato</span>";
    }

    public function bulletEsitoFinale()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:' . self::ESITI[$this->esito_finale] . ';"></span>';
        }

    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */




}

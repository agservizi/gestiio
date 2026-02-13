<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Comparasemplice extends Model
{
    protected $table = "comparasemplice";

    public const NOME_SINGOLARE = "segnalazione compara semplice";
    public const NOME_PLURALE = "segnalazioni compara semplice";

    protected $casts = [
        'data' => 'datetime',
        'tipo_segnalazione' => 'array'
        ,];

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7'
    ];


    public const SERVIZI = [
        'luce' => 'Luce',
        'gas' => 'Gas',
        'internet' => 'Internet',
        'mutui-prestiti' => 'Mutui e prestiti',
        'RC Auto' => 'RC Auto',
        'cessione-del-quinto' => 'Cessione del quinto',
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


        static::saving(function (Comparasemplice $model) {

            $esito = EsitoComparasemplice::find($model->esito_id);
            $model->esito_finale = $esito->esito_finale;
            \Log::debug('saving');
            if (!$model->mese_pagamento && $model->esito_finale == 'ok') {
                //$now = now();
                //$model->mese_pagamento = $now->month . '_' . $now->year;
                $model->mese_pagamento = $model->created_at->month . '_' . $model->created_at->year;
            }

            if ($model->esito_finale != 'ok') {
                $model->mese_pagamento = null;
            }
        });

        self::saved(function ($model) {

        });

        self::deleted(function ($model) {
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
        return $this->morphMany(AllegatoServizio::class, 'allegato');
    }

    public function esito()
    {
        return $this->hasOne(EsitoComparasemplice::class, 'id', 'esito_id');
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

    public function tipoProdottoBlade()
    {
        return str_replace('App\Models\Servizio', '', $this->prodotto_type);
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

    public function tipoProdotto()
    {
        return str_replace('App\Models\\', '', $this->prodotto_type);
    }

    public function puoModificare($puoModificare)
    {
        return $puoModificare;
    }


}

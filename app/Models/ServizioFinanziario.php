<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServizioFinanziario extends Model
{

    protected $table = "servizi_finanziari";

    protected $casts = [
        'data' => 'datetime'
    ];

    public const NOME_SINGOLARE = "servizio finanziario";
    public const NOME_PLURALE = "servizi finanziari";

    public const TIPI_SERVIZI = [
        'ServizioPrestito' => 'Prestito',
        'ServizioPolizza' => 'Polizza',
        'ServizioMutuo' => 'Mutuo',
    ];

    public const TIPI_SERVIZI_FACILE = [
        'ServizioPolizzaFacile' => 'POLIZZA FACILE',
    ];


    public const LOGHI_SERVIZI = [
        'ServizioPrestito' => ['logo' => '/loghi/logo_euransa.png', 'size' => 'h-65px'],
        'ServizioPolizza' => ['logo' => '/loghi/logo_euransa.png', 'size' => 'h-65px'],
        'ServizioMutuo' => ['logo' => '/loghi/logo_euransa.png', 'size' => 'h-65px'],
        'ServizioPolizzaFacile' => ['logo' => '/loghi/logo_polizza_facile.png', 'size' => 'h-25px'],
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


        static::saving(function (ServizioFinanziario $model) {

            $esito = EsitoServizioFinanziario::find($model->esito_id);
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
            Log::debug(__CLASS__ . '->' . __FUNCTION__ . ' contratto: ' . $model->id . ' mese_pagamento ' . $model->mese_pagamento);
            if ($model->isDirty('mese_pagamento')) {
                if ($model->mese_pagamento) {
                    list($mese, $anno) = explode('_', $model->mese_pagamento);
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                    self::calcolaProduzioneServiziFinanziari($model->agente_id, $anno, $mese);

                }
                if ($model->getOriginal('mese_pagamento')) {
                    list($mese, $anno) = explode('_', $model->getOriginal('mese_pagamento'));
                    self::calcolaProduzioneServiziFinanziari($model->agente_id, $anno, $mese);

                }
            }


        });

        self::deleted(function ($model) {
            self::calcolaProduzioneServiziFinanziari($model->agente_id, $model->created_at->year, $model->created_at->month);
        });


    }


    protected static function calcolaProduzioneServiziFinanziari($userId, $anno, $mese)
    {
        Log::debug('calcolo servizi in pagamento per $userId:' . $userId . ' $anno:' . $anno . ' $mese:' . $mese);
        $meseAnnoPagamento = $mese . '_' . $anno;
        $inPagamento = ServizioFinanziario::withoutGlobalScope('filtroOperatore')
            ->where('mese_pagamento', $meseAnnoPagamento)
            ->where('agente_id', $userId)
            ->sum('provvigione_agente');

        //Log::debug('=>$start:' . $start->format('d/m/Y') . ' $end:' . $end->format('d/m/Y') . ' $ordiniOk:' . $ordiniOk . ' $ordiniKo:' . $ordiniKo . ' $ordini' . $ordini . ' chiamata da ' . debug_backtrace()[1]['function']);


        $p = ProduzioneOperatore::findOrNewMio($userId , $anno , $mese);

        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;

        $p->importo_servizi_finanziari = $inPagamento;
        $p->save();

        self::calcolaGuadagnoAgenzia($anno, $mese);

    }


    protected static function calcolaGuadagnoAgenzia($anno, $mese)
    {
        $guadagno = GuadagnoAgenzia::firstOrNew(['anno' => $anno, 'mese' => $mese]);
        $guadagno->calcolaGuadagnoServizi();

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
        return $this->hasOne(EsitoServizioFinanziario::class, 'id', 'esito_id');
    }

    public function prodotto()
    {
        return $this->morphTo();
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

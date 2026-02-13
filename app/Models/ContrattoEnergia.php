<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContrattoEnergia extends Model
{

    protected $table = "contratti_energia";

    public const NOME_SINGOLARE = "contratto energia";
    public const NOME_PLURALE = "contratti energia";


    protected $casts = [
        'data' => 'datetime',
        'data_rilascio' => 'datetime',
        'data_scadenza' => 'datetime',
        'permesso_soggiorno_scadenza' => 'datetime',
    ];

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7'
    ];

    public const TIPI_DOCUMENTO = [
        'carta_identita' => 'Carta di identità',
        'patente' => 'Patente di guida',
        'passaporto' => 'Passaporto',
        'altro' => 'Altro',
    ];


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {

            /** @var User|null $user */
            $user = Auth::user();
            if (!$user) {
                return;
            }

            if ($user->hasPermissionTo('agente')) {
                $builder->where('agente_id', $user->id);
            } elseif ($user->hasPermissionTo('supervisore')) {
                $builder->whereHas('mandato', function ($q) {
                    $q->where('agente_id', Auth::id());
                });
            }
        });


        static::saving(function (ContrattoEnergia $model) {

            if ($model->esito_id !== 'bozza') {
                $esito = EsitoContrattoEnergia::find($model->esito_id);
                $model->esito_finale = $esito->esito_finale;
            }

            $esito = EsitoContrattoEnergia::find($model->esito_id);
            $model->esito_finale = $esito->esito_finale;
            if (!$model->mese_pagamento && $model->esito_finale == 'ok') {
                $model->mese_pagamento = now()->format('m_Y');
            }

            if ($model->esito_finale != 'ok') {
                $model->mese_pagamento = null;
            }

            $model->testo_ricerca = $model->denominazione . '|' . $model->codice_contratto . '|' . $model->codice_fiscale;

        });


        self::saved(function ($model) {
            Log::debug(__CLASS__ . '->' . __FUNCTION__ . ' contratto: ' . $model->id . ' mese_pagamento ' . $model->mese_pagamento);
            if ($model->isDirty('mese_pagamento')) {
                if ($model->mese_pagamento) {
                    list($mese, $anno) = explode('_', $model->mese_pagamento);
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                    self::calcolaProduzioneContrattiEnergia($model->agente_id, $anno, $mese);

                }
                if ($model->getOriginal('mese_pagamento')) {
                    list($mese, $anno) = explode('_', $model->getOriginal('mese_pagamento'));
                    self::calcolaProduzioneContrattiEnergia($model->agente_id, $anno, $mese);

                }
            }


        });

        self::deleted(function ($model) {
            self::calcolaProduzioneContrattiEnergia($model->agente_id, $model->created_at->year, $model->created_at->month);
        });


    }

    protected static function calcolaProduzioneContrattiEnergia($userId, $anno, $mese)
    {
        Log::debug('calcolo contratti energia in pagamento per $userId:' . $userId . ' $anno:' . $anno . ' $mese:' . $mese);
        $meseAnnoPagamento = $mese . '_' . $anno;
        $inPagamento = self::withoutGlobalScope('filtroOperatore')
            ->where('mese_pagamento', $meseAnnoPagamento)
            ->where('agente_id', $userId)
            ->sum('provvigione_agente');

        //Log::debug('=>$start:' . $start->format('d/m/Y') . ' $end:' . $end->format('d/m/Y') . ' $ordiniOk:' . $ordiniOk . ' $ordiniKo:' . $ordiniKo . ' $ordini' . $ordini . ' chiamata da ' . debug_backtrace()[1]['function']);


        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;

        $p->importo_contratti_energia = $inPagamento;
        $p->save();

        //self::calcolaGuadagnoAgenzia($anno, $mese);

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
        return $this->hasMany(AllegatoContrattoEnergia::class, 'contratto_energia_id');
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id', 'cliente_id');
    }


    public function gestore()
    {
        return $this->hasOne(GestoreContrattoEnergia::class, 'id', 'gestore_id');
    }

    public function esito()
    {
        return $this->hasOne(EsitoContrattoEnergia::class, 'id', 'esito_id');
    }

    public function mandato()
    {
        return $this->hasMany(Mandato::class, 'gestore_id', 'gestore_id');
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
    public function scopeDelCliente(Builder $queryBuilder)
    {
        $queryBuilder->withoutGlobalScope('filtroOperatore')
            ->whereHas('cliente.utente', function ($q) {
                $q->where('id', Auth::id());
            })
            ->with('tipoContratto.gestore')
            ->with('esito')
            ->with('comune')
            ->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    public function nominativo()
    {
        return $this->denominazione;
    }


    public function bulletEsitoFinale()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:' . self::ESITI[$this->esito_finale] . ';"></span>';
        }

    }


    public function labelPagato()
    {
        if ($this->pagato) return "<span class='badge badge-success' >Pagato</span>";
    }

    public static function selected($id)
    {
        if ($id) {
            $record = ContrattoTelefonia::find($id);
            if ($record) {
                return '<option value="' . $record->id . '">' . $record->nominativo() . ' - ' . $record->tipoContratto->nome . '</option>';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */


    public function puoModificare($puoModificare)
    {
        if ($puoModificare) {
            return true;
        }

        if ($this->esito_id == 'bozza' || $this->esito_id == 'da-gestire') {
            return true;
        } else {
            return false;
        }
    }
}

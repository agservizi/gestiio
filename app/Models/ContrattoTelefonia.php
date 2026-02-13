<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContrattoTelefonia extends Model
{

    protected $table = "contratti";

    public const NOME_SINGOLARE = "contratto telefonia";
    public const NOME_PLURALE = "contratti telefonia";


    protected $casts = [
        'data' => 'datetime',
        'data_rilascio' => 'datetime',
        'data_scadenza' => 'datetime',
        'permesso_soggiorno_scadenza' => 'datetime',
        'sollecito_gestore' => 'datetime',
        'data_reminder' => 'datetime',
        'reminder_inviato' => 'datetime',
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
        'permesso_soggiorno' => 'Permesso di soggiorno',
    ];


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::user()->hasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            } elseif (Auth::user()->hasPermissionTo('supervisore')) {
                $builder->whereHas('mandato', function ($q) {
                    $q->where('agente_id', Auth::id());
                });
            }
        });


        static::saving(function (ContrattoTelefonia $model) {

            $esito = EsitoTelefonia::find($model->esito_id);
            $model->esito_finale = $esito->esito_finale;

            if (!$model->mese_pagamento && $model->esito_finale == 'ok') {
                $model->mese_pagamento = now()->format('m_Y');
            }

            if ($model->esito_finale != 'ok') {
                $model->mese_pagamento = null;
            }
        });

        self::saved(function ($model) {
            Log::debug(__CLASS__ . '->' . __FUNCTION__ . ' contratto: ' . $model->id . ' mese_pagamento ' . $model->mese_pagamento);
            ProduzioneOperatore::calcolaTotaliOrdiniMese($model->agente_id, $model->created_at->year, $model->created_at->month);

            if ($model->isDirty('mese_pagamento')) {
                if ($model->mese_pagamento) {
                    list($mese, $anno) = explode('_', $model->mese_pagamento);
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                }
                if ($model->getOriginal('mese_pagamento')) {
                    list($mese, $anno) = explode('_', $model->getOriginal('mese_pagamento'));
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                }
            }


        });

        self::deleted(function ($model) {
            ProduzioneOperatore::calcolaTotaliOrdiniMese($model->agente_id, $model->created_at->year, $model->created_at->month);
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
        return $this->hasMany(AllegatoContratto::class, 'contratto_id');
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id', 'cliente_id');
    }

    public function comune()
    {
        return $this->hasOne(Comune::class, 'id', 'citta');
    }

    public function esito()
    {
        return $this->hasOne(EsitoTelefonia::class, 'id', 'esito_id');
    }

    public function mandato()
    {
        return $this->hasManyThrough(Mandato::class, TipoContratto::class, 'id', 'gestore_id', 'tipo_contratto_id', 'gestore_id');
    }


    public function prodotto()
    {
        return $this->morphTo();
    }

    public function tipoContratto()
    {
        return $this->hasOne(TipoContratto::class, 'id', 'tipo_contratto_id');
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
        return $this->cognome . ' ' . $this->nome;
    }

    public function denominazione()
    {
        return $this->ragione_sociale ?: ($this->cognome . ' ' . $this->nome);
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


    protected static function determinaMesePagamento($model)
    {
        if ($model->creatoQuestoMese()) {
            $model->mese_pagamento = now()->format('m_Y');
        } else {
            $_15delMese = Carbon::createFromDate(null, null, config('configurazione.giornoDelMesePagamento'));
            if ($_15delMese->isPast()) {
                $mesePagamento = now()->format('m_Y');
            } else {
                $mesePagamento = now()->subMonths()->format('m_Y');
            }
            $model->mese_pagamento = $mesePagamento;
        }


    }

    public function creatoQuestoMese()
    {
        return $this->created_at->format('m_Y') == now()->format('m_Y');
    }



    public static function determinaPuoModificare()
    {
        return Auth::user()->hasAnyPermission(['admin', 'supervisore']);

    }

    public static function determinaPuoCambiareStato()
    {
        return self::determinaPuoModificare() || Auth::user()->hasPermissionTo('supervisore');

    }

    public static function determinaPuoCreare()
    {
        return Auth::user()->hasAnyPermission(['admin', 'agente']);

    }


}

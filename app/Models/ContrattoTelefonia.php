<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ContrattoTelefonia extends Model
{
    protected $table = 'contratti';

    public const NOME_SINGOLARE = 'contratto telefonia';

    public const NOME_PLURALE = 'contratti telefonia';

    protected $casts = [
        'data' => 'datetime',
        'data_rilascio' => 'datetime',
        'data_scadenza' => 'datetime',
        'permesso_soggiorno_scadenza' => 'datetime',
        'sollecito_gestore' => 'datetime',
        'data_reminder' => 'datetime',
        'reminder_inviato' => 'datetime',
        'ok_at' => 'datetime',
        'copertura_verificata_at' => 'datetime',
        'copertura_payload' => 'array',
    ];

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7',
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
            $user = Auth::user();
            // Affiliato + supervisore whitelist (es. AG00062): solo contratti propri.
            if ($user instanceof User && $user->actsAsAgenteForContratti()) {
                $builder->where('agente_id', Auth::id());
            } elseif (self::authUserHasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            } elseif (self::authUserHasPermissionTo('supervisore')) {
                $builder->whereHas('mandato', function ($q) {
                    $q->where('agente_id', Auth::id());
                });
            }
        });

        static::saving(function (ContrattoTelefonia $model) {
            if (self::hasInternalCodeColumn() && ! $model->codice_contratto_interno && $model->id) {
                $model->codice_contratto_interno = self::buildInternalContractCode((int) $model->id);
            }

            $esito = EsitoTelefonia::find($model->esito_id);
            $model->esito_finale = $esito?->esito_finale;

            if (! $model->mese_pagamento && $model->esito_finale == 'ok') {
                $model->mese_pagamento = now()->format('m_Y');
            }

            if ($model->esito_finale == 'ok' && ! $model->ok_at) {
                $model->ok_at = now();
            }

            if ($model->esito_finale != 'ok') {
                if ($model->getOriginal('esito_finale') === 'ok') {
                    try {
                        app(\App\Http\Services\Billing\ProvvigioneClawbackService::class)->captureBeforeKo($model);
                    } catch (\Throwable $e) {
                        \Log::warning('ContrattoTelefonia clawback capture', ['id' => $model->id, 'error' => $e->getMessage()]);
                    }
                }
                $model->mese_pagamento = null;
                $model->ok_at = null;
            }
        });

        self::saved(function ($model) {
            Log::debug(__CLASS__.'->'.__FUNCTION__.' contratto: '.$model->id.' mese_pagamento '.$model->mese_pagamento);
            ProduzioneOperatore::calcolaTotaliOrdiniMese($model->agente_id, $model->created_at->year, $model->created_at->month);

            if ($model->wasChanged('mese_pagamento') || $model->wasChanged('pagato')) {
                $mesi = [];
                if ($model->mese_pagamento) {
                    $mesi[] = $model->mese_pagamento;
                }
                if ($model->getOriginal('mese_pagamento')) {
                    $mesi[] = $model->getOriginal('mese_pagamento');
                }
                foreach (array_unique(array_filter($mesi)) as $mesePagamento) {
                    [$mese, $anno] = explode('_', $mesePagamento);
                    $p = ProduzioneOperatore::findOrNewMio($model->agente_id, $anno, $mese);
                    if ($p->exists && $p->fattura_proforma_id) {
                        continue;
                    }
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                }
            }

            if ($model->esito_finale === 'ok') {
                try {
                    app(\App\Http\Services\Billing\ProvvigioneClawbackService::class)->openOrRefresh($model);
                } catch (\Throwable $e) {
                    \Log::warning('ContrattoTelefonia clawback open', ['id' => $model->id, 'error' => $e->getMessage()]);
                }
            }

            try {
                app(\App\Http\Services\AffiliateReferralService::class)
                    ->onContrattoCaricato((int) $model->agente_id);
            } catch (\Throwable $e) {
                \Log::warning('ContrattoTelefonia referral eval', ['id' => $model->id, 'error' => $e->getMessage()]);
            }
        });

        self::deleted(function ($model) {
            ProduzioneOperatore::calcolaTotaliOrdiniMese($model->agente_id, $model->created_at->year, $model->created_at->month);
            if ($model->mese_pagamento) {
                [$mese, $anno] = explode('_', $model->mese_pagamento);
                $p = ProduzioneOperatore::findOrNewMio($model->agente_id, $anno, $mese);
                if (! ($p->exists && $p->fattura_proforma_id)) {
                    ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->agente_id, $anno, $mese);
                }
            }
            try {
                app(\App\Http\Services\AffiliateReferralService::class)
                    ->onContrattoCaricato((int) $model->agente_id);
            } catch (\Throwable $e) {
                \Log::warning('ContrattoTelefonia referral eval on delete', ['id' => $model->id, 'error' => $e->getMessage()]);
            }
        });

        self::created(function (ContrattoTelefonia $model) {
            if (! $model->codice_contratto_interno && self::hasInternalCodeColumn()) {
                $model->codice_contratto_interno = self::buildInternalContractCode((int) $model->id);
                $model->saveQuietly();
            }
        });

    }

    /**
     * Contratti ancora da liquidare all'affiliato (esclude già pagati).
     */
    public function scopeDaLiquidare($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('pagato')->orWhere('pagato', 0)->orWhere('pagato', false);
        });
    }

    public function scopeEleggibileAutofattura($query)
    {
        $cutoff = now()->subMonths(2)->toDateTimeString();

        return $query->daLiquidare()
            ->where('esito_finale', 'ok')
            ->where(function ($q) {
                $q->whereNull('autofattura_billing_document_id')
                    ->orWhere('autofattura_billing_document_id', 0);
            })
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('ok_at')->where('ok_at', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('ok_at')
                        ->whereNotNull('mese_pagamento')
                        ->whereRaw(
                            "STR_TO_DATE(CONCAT('01_', REPLACE(mese_pagamento, '-', '_')), '%d_%m_%Y') <= ?",
                            [substr($cutoff, 0, 10)]
                        );
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente()
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function allegati()
    {
        return $this->hasMany(AllegatoContratto::class, 'contratto_id');
    }

    public function caricatoDa()
    {
        return $this->belongsTo(User::class, 'caricato_da_user_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function comune()
    {
        return $this->belongsTo(Comune::class, 'citta');
    }

    public function coperturaRichiesta()
    {
        return $this->belongsTo(CoperturaRichiesta::class, 'copertura_richiesta_id');
    }

    public function esito()
    {
        return $this->belongsTo(EsitoTelefonia::class, 'esito_id');
    }

    public function latestRiskSignal()
    {
        return $this->hasOne(CustomerRiskSignal::class, 'contract_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('contract_type', 'contratto-telefonia');
            });
    }

    public function latestOtpSigningRelay()
    {
        return $this->hasOne(ContractSigningRelay::class, 'contract_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('module', ContractSigningRelay::MODULE_TELEFONIA)
                    ->where('kind', ContractSigningRelay::KIND_OTP);
            });
    }

    public function hasOtpFirmaScaduto(): bool
    {
        if (($this->firma_digitale_status ?? '') !== 'awaiting_otp') {
            return false;
        }

        $relay = $this->latestOtpSigningRelay;

        return $relay !== null && $relay->isExpired();
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
        return $this->belongsTo(TipoContratto::class, 'tipo_contratto_id');
    }

    public function telefoniaOfferta()
    {
        return $this->belongsTo(TelefoniaOfferta::class, 'telefonia_offerta_id');
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
        return trim($this->ragione_sociale ?: ($this->cognome.' '.$this->nome));
    }

    public function denominazione()
    {
        return $this->ragione_sociale ?: ($this->cognome.' '.$this->nome);
    }

    public function bulletEsitoFinale()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:'.self::ESITI[$this->esito_finale].';"></span>';
        }

    }

    public function labelPagato()
    {
        if ($this->pagato) {
            return \App\Http\HelperForMetronic::statusBadge('Pagato', 'success');
        }

        return \App\Http\HelperForMetronic::statusBadge('Non pagato', 'danger');
    }

    public static function selected($id)
    {
        if ($id) {
            $record = ContrattoTelefonia::find($id);
            if ($record) {
                return '<option value="'.$record->id.'">'.$record->nominativo().' - '.$record->tipoContratto->nome.'</option>';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public function isStatoApertoPerModifica(): bool
    {
        $editableEsiti = ['bozza', 'da-gestire', 'in-gestione', 'in_gestione'];
        if (in_array((string) $this->esito_id, $editableEsiti, true)) {
            return true;
        }

        $nomeEsito = strtolower(trim((string) optional($this->esito)->nome));
        $nomeNorm = preg_replace('/[\s_\-]+/', ' ', $nomeEsito) ?? '';
        if (in_array($nomeNorm, ['in gestione', 'da gestire', 'bozza'], true)) {
            return true;
        }

        return false;
    }

    public function puoModificare($puoModificare)
    {
        if ($puoModificare) {
            return true;
        }

        return $this->isStatoApertoPerModifica();
    }

    public function puoRiaprire(?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        // Bozza / Da gestire / In Gestione: niente Riapri (si usa Modifica)
        if ($this->isStatoApertoPerModifica()) {
            return false;
        }

        if ($user->hasAnyPermission(['admin', 'supervisore', 'operatore'])) {
            return true;
        }

        if ($user->hasPermissionTo('agente') && (int) $this->agente_id === (int) $user->id) {
            return true;
        }

        return false;
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

    protected static function buildInternalContractCode(int $id): string
    {
        return 'TEL'.str_pad((string) $id, 11, '0', STR_PAD_LEFT);
    }

    protected static function hasInternalCodeColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn((new static)->getTable(), 'codice_contratto_interno');
        }

        return $hasColumn;
    }

    public function creatoQuestoMese()
    {
        return $this->created_at->format('m_Y') == now()->format('m_Y');
    }

    public static function determinaPuoModificare()
    {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user->canBypassContrattoEditLock();
        }

        return false;
    }

    public static function determinaPuoCambiareStato()
    {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user->canManageContrattoSegnalazione() || $user->canBypassContrattoEditLock();
        }

        return false;
    }

    public static function determinaPuoCreare()
    {
        $user = Auth::user();
        if ($user instanceof User && $user->canCreateContrattiAffiliato()) {
            return true;
        }

        return self::authUserHasAnyPermission(['admin', 'agente']);
    }

    protected static function authUserHasPermissionTo(string $permission): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return (bool) call_user_func([$user, 'hasPermissionTo'], $permission);
        }

        return method_exists($user, 'can')
            ? (bool) call_user_func([$user, 'can'], $permission)
            : false;
    }

    protected static function authUserHasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyPermission')) {
            return (bool) call_user_func([$user, 'hasAnyPermission'], $permissions);
        }

        if (method_exists($user, 'can')) {
            foreach ($permissions as $permission) {
                if ((bool) call_user_func([$user, 'can'], $permission)) {
                    return true;
                }
            }
        }

        return false;
    }
}

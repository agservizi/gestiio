<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ContrattoEnergia extends Model
{
    protected $table = 'contratti_energia';

    public const NOME_SINGOLARE = 'contratto energia';

    public const NOME_PLURALE = 'contratti energia';

    protected $casts = [
        'data' => 'datetime',
        'data_rilascio' => 'datetime',
        'data_scadenza' => 'datetime',
        'data_fine' => 'date',
        'data_reminder' => 'date',
        'reminder_inviato' => 'datetime',
        'permesso_soggiorno_scadenza' => 'datetime',
        'ok_at' => 'datetime',
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
            if (! $user) {
                return;
            }

            // Affiliato + supervisore whitelist (es. AG00062): solo contratti propri.
            if ($user->actsAsAgenteForContratti()) {
                $builder->where('agente_id', $user->id);
            } elseif ($user->hasPermissionTo('agente')) {
                $builder->where('agente_id', $user->id);
            } elseif ($user->hasPermissionTo('supervisore')) {
                $builder->whereHas('mandato', function ($q) {
                    $q->where('agente_id', Auth::id());
                });
            }
        });

        static::saving(function (ContrattoEnergia $model) {
            if (self::hasInternalCodeColumn() && ! $model->codice_contratto_interno && $model->id) {
                $model->codice_contratto_interno = self::buildInternalContractCode((int) $model->id);
            }

            if ($model->esito_id !== 'bozza') {
                $esito = EsitoContrattoEnergia::find($model->esito_id);
                $model->esito_finale = $esito->esito_finale;
            }

            $esito = EsitoContrattoEnergia::find($model->esito_id);
            $model->esito_finale = $esito->esito_finale;
            if (! $model->mese_pagamento && $model->esito_finale == 'ok') {
                $model->mese_pagamento = now()->format('m_Y');
            }

            if ($model->esito_finale == 'ok' && ! $model->ok_at) {
                $model->ok_at = now();
            }

            // Reminder rinnovo da data_fine contratto (non da EnergiaOfferta.valido_al).
            if (Schema::hasColumn($model->getTable(), 'data_fine')
                && Schema::hasColumn($model->getTable(), 'data_reminder')
                && $model->data_fine
                && ! $model->data_reminder) {
                $model->data_reminder = \Carbon\Carbon::parse($model->data_fine)->subDays(30);
            }

            if ($model->esito_finale != 'ok') {
                if ($model->getOriginal('esito_finale') === 'ok') {
                    try {
                        app(\App\Http\Services\Billing\ProvvigioneClawbackService::class)->captureBeforeKo($model);
                    } catch (\Throwable $e) {
                        \Log::warning('ContrattoEnergia clawback capture', ['id' => $model->id, 'error' => $e->getMessage()]);
                    }
                }
                $model->mese_pagamento = null;
                $model->ok_at = null;
            }

            $model->testo_ricerca = $model->denominazione.'|'.$model->codice_contratto.'|'.$model->codice_contratto_interno.'|'.$model->codice_fiscale;

        });

        self::saved(function ($model) {
            Log::debug(__CLASS__.'->'.__FUNCTION__.' contratto: '.$model->id.' mese_pagamento '.$model->mese_pagamento);
            // Conteggio mese (tel+energia) sul created_at — come ContrattoTelefonia
            if ($model->agente_id && $model->created_at) {
                ProduzioneOperatore::calcolaTotaliOrdiniMese(
                    (int) $model->agente_id,
                    (int) $model->created_at->year,
                    (int) $model->created_at->month
                );
            }
            if ($model->wasChanged('mese_pagamento') || $model->wasChanged('pagato') || $model->wasChanged('provvigione_agente')
                || $model->wasChanged('fattura_proforma_id') || $model->wasChanged('autofattura_billing_document_id')) {
                $mesi = [];
                if ($model->mese_pagamento) {
                    $mesi[] = $model->mese_pagamento;
                }
                if ($model->getOriginal('mese_pagamento')) {
                    $mesi[] = $model->getOriginal('mese_pagamento');
                }
                foreach (array_unique(array_filter($mesi)) as $mesePagamento) {
                    [$mese, $anno] = explode('_', $mesePagamento);
                    self::ricalcolaProduzioneSeAperta((int) $model->agente_id, $anno, $mese);
                }
            }
            if ($model->esito_finale === 'ok') {
                try {
                    app(\App\Http\Services\Billing\ProvvigioneClawbackService::class)->openOrRefresh($model);
                } catch (\Throwable $e) {
                    \Log::warning('ContrattoEnergia clawback open', ['id' => $model->id, 'error' => $e->getMessage()]);
                }
            }

            try {
                app(\App\Http\Services\AffiliateReferralService::class)
                    ->onContrattoCaricato((int) $model->agente_id);
            } catch (\Throwable $e) {
                \Log::warning('ContrattoEnergia referral eval', ['id' => $model->id, 'error' => $e->getMessage()]);
            }
        });

        self::deleted(function ($model) {
            if ($model->agente_id && $model->created_at) {
                ProduzioneOperatore::calcolaTotaliOrdiniMese(
                    (int) $model->agente_id,
                    (int) $model->created_at->year,
                    (int) $model->created_at->month
                );
            }
            if ($model->mese_pagamento) {
                [$mese, $anno] = explode('_', $model->mese_pagamento);
                self::ricalcolaProduzioneSeAperta((int) $model->agente_id, $anno, $mese);
            } else {
                self::ricalcolaProduzioneSeAperta((int) $model->agente_id, $model->created_at->year, $model->created_at->month);
            }
            try {
                app(\App\Http\Services\AffiliateReferralService::class)
                    ->onContrattoCaricato((int) $model->agente_id);
            } catch (\Throwable $e) {
                \Log::warning('ContrattoEnergia referral eval on delete', ['id' => $model->id, 'error' => $e->getMessage()]);
            }
        });

        self::created(function (ContrattoEnergia $model) {
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

    /**
     * OK da oltre 2 mesi, non pagati, non già in autofattura.
     */
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

    protected static function ricalcolaProduzioneSeAperta(int $userId, $anno, $mese): void
    {
        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        if ($p->exists && $p->fattura_proforma_id) {
            return;
        }

        // Conteggio "caricati" resta su created_at (hook saved/deleted), non sul mese_pagamento.
        ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($userId, $anno, $mese);
        self::calcolaProduzioneContrattiEnergia($userId, $anno, $mese);
    }

    public static function calcolaProduzioneContrattiEnergia($userId, $anno, $mese)
    {
        Log::debug('calcolo contratti energia in pagamento per $userId:'.$userId.' $anno:'.$anno.' $mese:'.$mese);
        $keys = [
            meseStrPad($mese).'_'.$anno,
            ((int) $mese).'_'.$anno,
        ];
        $inPagamento = self::withoutGlobalScope('filtroOperatore')
            ->daLiquidare()
            ->whereIn('mese_pagamento', array_unique($keys))
            ->where('agente_id', $userId)
            ->with('gestore')
            ->get()
            ->sum(function (self $c) {
                return $c->isNonLiquidabile() ? 0.0 : (float) ($c->provvigione_agente ?? 0);
            });

        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;

        $p->importo_contratti_energia = $inPagamento;
        $p->save();
    }

    protected static function buildInternalContractCode(int $id): string
    {
        return 'OP'.str_pad((string) $id, 11, '0', STR_PAD_LEFT);
    }

    protected static function hasInternalCodeColumn(): bool
    {
        static $hasColumn = null;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn((new static)->getTable(), 'codice_contratto_interno');
        }

        return $hasColumn;
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
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function allegati()
    {
        return $this->hasMany(AllegatoContrattoEnergia::class, 'contratto_energia_id');
    }

    public function caricatoDa()
    {
        return $this->belongsTo(User::class, 'caricato_da_user_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function gestore()
    {
        return $this->belongsTo(GestoreContrattoEnergia::class, 'gestore_id');
    }

    public function energiaOfferta()
    {
        return $this->belongsTo(EnergiaOfferta::class, 'energia_offerta_id');
    }

    public function esito()
    {
        return $this->belongsTo(EsitoContrattoEnergia::class, 'esito_id');
    }

    public function latestRiskSignal()
    {
        return $this->hasOne(CustomerRiskSignal::class, 'contract_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('contract_type', 'contratto-energia');
            });
    }

    public function latestOtpSigningRelay()
    {
        return $this->hasOne(ContractSigningRelay::class, 'contract_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('module', ContractSigningRelay::MODULE_ENERGIA)
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

    public function tipoAttivazioneKey(): ?string
    {
        $tipo = trim((string) ($this->tipo_attivazione ?? ''));
        if ($tipo !== '') {
            return $tipo;
        }

        $prodotto = $this->relationLoaded('prodotto') ? $this->prodotto : null;
        if ($prodotto && isset($prodotto->tipo_attivazione)) {
            $tipo = trim((string) $prodotto->tipo_attivazione);
            if ($tipo !== '') {
                return $tipo;
            }
        }

        return null;
    }

    public function labelTipoAttivazione(): ?string
    {
        return app(\App\Http\Services\EnergiaComposizioneService::class)
            ->labelTipoAttivazione($this->tipoAttivazioneKey());
    }

    public function isNonLiquidabile(): bool
    {
        return ! app(\App\Http\Services\EnergiaComposizioneService::class)
            ->isProvvigioneEligible($this->tipoAttivazioneKey(), $this->gestorePerLiquidabilita());
    }

    public function labelNonLiquidabile(): ?string
    {
        if (! $this->isNonLiquidabile()) {
            return null;
        }

        return app(\App\Http\Services\EnergiaComposizioneService::class)
            ->nonLiquidabileMessage($this->tipoAttivazioneKey(), $this->gestorePerLiquidabilita());
    }

    /**
     * Gestore con switch_key affidabile (gli eager load parziali spesso omettono la colonna).
     */
    protected function gestorePerLiquidabilita(): ?GestoreContrattoEnergia
    {
        $gestore = $this->relationLoaded('gestore') ? $this->gestore : null;
        if ($gestore && filled($gestore->switch_key)) {
            return $gestore;
        }

        return $this->gestore()->first();
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
}

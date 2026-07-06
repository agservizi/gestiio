<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CafPatronato extends Model
{
    protected $table = 'caf_patronato';

    public const NOME_SINGOLARE = 'pratica caf patronato';

    public const NOME_PLURALE = 'pratiche caf patronato';

    protected $casts = [
        'data' => 'datetime',
    ];

    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function (CafPatronato $model) {
            if (! $model->codice_pratica) {
                $model->codice_pratica = self::generaCodicePratica();
            }
        });

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            }
        });

    }

    public static function puoModificareEsito()
    {
        return Auth::user()->hasAnyPermission(['admin', 'supervisore']);
    }

    public static function puoModificare()
    {
        return ! Auth::user()->hasPermissionTo('supervisore');
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
        return $this->hasMany(AllegatoCafPatronato::class, 'caf_patronato_id');
    }

    public function allegatiPerCliente()
    {
        return $this->hasMany(AllegatoCafPatronato::class, 'caf_patronato_id')->where('per_cliente', 1);
    }

    public function caricatoDa()
    {
        return $this->belongsTo(User::class, 'caricato_da_user_id');
    }

    public function esito()
    {
        return $this->belongsTo(EsitoCafPatronato::class, 'esito_id');
    }

    public function prodotto()
    {
        return $this->morphTo();
    }

    public function tipo()
    {
        return $this->belongsTo(TipoCafPatronato::class, 'tipo_caf_patronato_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeAperte(Builder $builder): Builder
    {
        return $builder->whereNull('esito_finale');
    }

    public function scopeInLavorazione(Builder $builder): Builder
    {
        return $builder->whereIn('esito_id', ['bozza', 'da-gestire']);
    }

    public function scopeFerme(Builder $builder, int $giorni = 7): Builder
    {
        return $builder->inLavorazione()
            ->whereDate('created_at', '<=', now()->subDays($giorni));
    }

    public function scopeDelMese(Builder $builder, int $anno, int $mese): Builder
    {
        return $builder->whereYear('data', $anno)
            ->whereMonth('data', $mese);
    }

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */
    public function nominativo()
    {
        return $this->cognome.' '.$this->nome;
    }

    public function tipoProdottoBlade()
    {
        return str_replace('App\Models\CafPat', '', $this->prodotto_type);
    }

    public function labelPagato()
    {
        if ($this->pagato) {
            return "<span class='badge badge-success' >Pagato</span>";
        }
    }

    public function bulletEsitoFinale()
    {
        if ($this->esito_finale && isset(self::ESITI[$this->esito_finale])) {
            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style="background-color:'.self::ESITI[$this->esito_finale].'"></span>';
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

    public static function generaCodicePratica(): string
    {
        do {
            $codice = 'CAF'.Str::upper((string) Str::ulid());
        } while (self::withoutGlobalScope('filtroOperatore')->where('codice_pratica', $codice)->exists());

        return $codice;
    }
}

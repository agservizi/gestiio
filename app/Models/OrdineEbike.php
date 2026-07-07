<?php

namespace App\Models;

use App\Enums\OrdineEbikeStatoEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class OrdineEbike extends Model
{
    protected $table = 'ordini_ebike';

    public const NOME_SINGOLARE = 'ordine ebike';

    public const NOME_PLURALE = 'ordini ebike';

    public const GIORNI_SLA_SPEDIZIONE = 10;

    protected $fillable = [
        'agente_id',
        'stato',
        'totale',
        'note',
        'cro_bonifico',
        'data_bonifico_dichiarata',
        'ricevuta_bonifico',
        'pagamento_confermato_da',
        'pagamento_confermato_at',
        'corriere',
        'tracking_number',
        'spedito_at',
        'scadenza_spedizione',
        'consegnato_at',
        'sla_alert_inviato',
        'annullato_motivo',
    ];

    protected $casts = [
        'stato' => OrdineEbikeStatoEnum::class,
        'totale' => 'decimal:2',
        'data_bonifico_dichiarata' => 'date',
        'pagamento_confermato_at' => 'datetime',
        'spedito_at' => 'datetime',
        'scadenza_spedizione' => 'date',
        'consegnato_at' => 'datetime',
        'sla_alert_inviato' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('filtroAgente', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente') && ! Auth::user()->hasPermissionTo('admin')) {
                $builder->where('agente_id', Auth::id());
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function confermatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagamento_confermato_da');
    }

    public function righe(): HasMany
    {
        return $this->hasMany(RigaOrdineEbike::class, 'ordine_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeInSlaScaduta(Builder $query): Builder
    {
        return $query->where('stato', OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO->value)
            ->where('sla_alert_inviato', false)
            ->whereNotNull('scadenza_spedizione')
            ->whereDate('scadenza_spedizione', '<', now()->toDateString());
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public function ricalcolaTotale(): void
    {
        $this->totale = $this->righe()->get()->sum(fn (RigaOrdineEbike $riga) => $riga->subtotale());
        $this->save();
    }

    public function scadenzaSuperata(): bool
    {
        return $this->stato === OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO
            && $this->scadenza_spedizione !== null
            && $this->scadenza_spedizione->isPast();
    }

    public function urlRicevutaBonifico(): ?string
    {
        return $this->ricevuta_bonifico ? '/storage/'.$this->ricevuta_bonifico : null;
    }
}

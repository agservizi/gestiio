<?php

namespace App\Models;

use App\Enums\FatturaProformaStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use function applicaIva;

class FatturaProforma extends Model
{
    protected $table = 'fatture_proforma';

    public const NOME_SINGOLARE = 'fattura proforma';

    public const NOME_PLURALE = 'fatture proforma';

    protected $casts = [
        'data' => 'date',
        'status' => FatturaProformaStatus::class,
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
            if ($user && $user->hasPermissionTo('agente') && ! $user->hasPermissionTo('admin') && ! $user->hasRole('admin')) {
                $builder->whereHas('intestazione.agente', function ($q) {
                    $q->where('id', Auth::id());
                });
            }
        });

        static::saving(function (FatturaProforma $model) {
            if ($model->totale_imponibile) {
                $model->totale_con_iva = applicaIva($model->totale_imponibile, $model->aliquota_iva);
            }
        });

        static::creating(function (FatturaProforma $model) {
            if (! $model->status) {
                $model->status = FatturaProformaStatus::BOZZA;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function righe()
    {
        return $this->hasMany(RigaFatturaProforma::class, 'fattura_proforma_id', 'id');
    }

    public function intestazione()
    {
        return $this->belongsTo(IntestazioneFatturaProforma::class, 'intestazione_id');
    }

    public function produzione()
    {
        return $this->hasOne(ProduzioneOperatore::class, 'fattura_proforma_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function statusEnum(): FatturaProformaStatus
    {
        if ($this->status instanceof FatturaProformaStatus) {
            return $this->status;
        }

        return FatturaProformaStatus::tryFrom((string) $this->status) ?? FatturaProformaStatus::BOZZA;
    }

    public function statusBadgeClass(): string
    {
        return $this->statusEnum()->badgeClass();
    }

    public function statusLabel(): string
    {
        return $this->statusEnum()->label();
    }

    public function periodoLabel(): ?string
    {
        $produzione = $this->produzione;
        if (! $produzione) {
            return null;
        }

        return str_pad((string) $produzione->mese, 2, '0', STR_PAD_LEFT).'/'.$produzione->anno;
    }
}

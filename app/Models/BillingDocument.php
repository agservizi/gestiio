<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingDocument extends Model
{
    public const SOURCE_CAF_MONTHLY = 'caf_monthly';

    public const SOURCE_SEND_MONTHLY = 'send_monthly';

    public const SOURCE_AGENT_PROFORMA = 'agent_proforma';

    protected $fillable = [
        'source',
        'periodo',
        'idempotency_key',
        'status',
        'invoiceshelf_type',
        'invoiceshelf_id',
        'unique_hash',
        'gestiio_subject_type',
        'gestiio_subject_id',
        'totale',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'totale' => 'float',
        'invoiceshelf_id' => 'integer',
    ];

    public function gestiioSubject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === 'pagata';
    }

    public function labelSource(): string
    {
        return match ($this->source) {
            self::SOURCE_CAF_MONTHLY => 'Proforma CAF/Patronato',
            self::SOURCE_SEND_MONTHLY => 'Proforma SEND',
            self::SOURCE_AGENT_PROFORMA => 'Proforma agente',
            default => $this->source,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'bozza' => 'badge-light-secondary',
            'emessa' => 'badge-light-primary',
            'inviata' => 'badge-light-info',
            'pagata' => 'badge-light-success',
            default => 'badge-light',
        };
    }
}

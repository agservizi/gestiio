<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaraRisultato extends Model
{
    protected $table = 'gara_risultati';

    protected $guarded = [];

    protected $casts = [
        'valore_raggiunto' => 'integer',
        'premio' => 'float',
        'anno' => 'integer',
        'mese' => 'integer',
        'liquidato_at' => 'datetime',
    ];

    public function gara(): BelongsTo
    {
        return $this->belongsTo(Gara::class, 'gara_id');
    }

    public function soglia(): BelongsTo
    {
        return $this->belongsTo(GaraSoglia::class, 'gara_soglia_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fatturaProforma(): BelongsTo
    {
        return $this->belongsTo(FatturaProforma::class, 'fattura_proforma_id');
    }

    public function isCongelato(): bool
    {
        return (int) ($this->fattura_proforma_id ?? 0) > 0;
    }

    public function scopeDelMese($query, int $anno, int $mese)
    {
        return $query->where('anno', $anno)->where('mese', $mese);
    }

    public function scopePerUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

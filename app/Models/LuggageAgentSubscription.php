<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuggageAgentSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'billing_month',
        'amount',
        'movimento_portafoglio_id',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimentoPortafoglio(): BelongsTo
    {
        return $this->belongsTo(MovimentoPortafoglio::class, 'movimento_portafoglio_id');
    }
}

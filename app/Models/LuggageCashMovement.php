<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuggageCashMovement extends Model
{
    protected $fillable = [
        'luggage_deposit_id',
        'amount',
        'payment_method',
        'currency',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(LuggageDeposit::class, 'luggage_deposit_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

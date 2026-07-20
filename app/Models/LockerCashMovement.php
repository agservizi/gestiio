<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerCashMovement extends Model
{
    protected $fillable = [
        'locker_package_id',
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

    public function package(): BelongsTo
    {
        return $this->belongsTo(LockerPackage::class, 'locker_package_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

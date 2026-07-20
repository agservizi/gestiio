<?php

namespace App\Models;

use App\Enums\LockerPackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LockerPackage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const NOME_SINGOLARE = 'pacco locker';

    public const NOME_PLURALE = 'pacchi locker';

    protected $fillable = [
        'id',
        'station_id',
        'code',
        'qr_token',
        'cliente_id',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'sender_name',
        'sender_phone',
        'carrier',
        'tracking_code',
        'notes',
        'expected_pickup_date',
        'photo_path',
        'photo_taken_at',
        'received_by_user_id',
        'received_at',
        'delivered_at',
        'signature_path',
        'signer_name',
        'daily_rate',
        'total_amount',
        'payment_method',
        'status',
        'source',
        'cash_movement_id',
    ];

    protected $casts = [
        'expected_pickup_date' => 'date',
        'photo_taken_at' => 'datetime',
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
        'daily_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => LockerPackageStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $package) {
            if (empty($package->id)) {
                $package->id = (string) Str::ulid();
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(LockerStation::class, 'station_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(LockerCashMovement::class, 'cash_movement_id');
    }

    public function pickupUrl(): string
    {
        return url('/locker-point/ritiro/'.$this->id.'?t='.urlencode($this->qr_token));
    }
}

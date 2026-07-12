<?php

namespace App\Models;

use App\Enums\LuggageDepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LuggageDeposit extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const NOME_SINGOLARE = 'deposito bagagli';

    public const NOME_PLURALE = 'depositi bagagli';

    protected $fillable = [
        'id',
        'code',
        'qr_token',
        'cliente_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'bag_count',
        'bag_tags',
        'notes',
        'booking_date',
        'expected_check_in',
        'expected_check_out',
        'checked_in_at',
        'checked_out_at',
        'daily_rate',
        'total_amount',
        'payment_method',
        'status',
        'source',
        'cash_movement_id',
    ];

    protected $casts = [
        'bag_tags' => 'array',
        'booking_date' => 'date',
        'expected_check_in' => 'datetime',
        'expected_check_out' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'daily_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => LuggageDepositStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $deposit) {
            if (empty($deposit->id)) {
                $deposit->id = (string) Str::ulid();
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(LuggageCashMovement::class, 'cash_movement_id');
    }

    public function verifyUrl(): string
    {
        return url("/deposito-bagagli/verify/{$this->id}?t={$this->qr_token}");
    }

    public function pickupUrl(): string
    {
        return url("/deposito-bagagli/ritiro/{$this->id}?t={$this->qr_token}");
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }
}

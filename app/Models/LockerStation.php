<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LockerStation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'slug',
        'name',
        'daily_rate',
        'currency',
        'max_capacity',
        'min_days',
        'max_packages_per_booking',
        'online_intake_enabled',
        'api_enabled',
        'api_key_hash',
        'api_key_prefix',
        'api_requested_at',
        'api_enabled_at',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'max_capacity' => 'integer',
        'min_days' => 'integer',
        'max_packages_per_booking' => 'integer',
        'online_intake_enabled' => 'boolean',
        'api_enabled' => 'boolean',
        'api_requested_at' => 'datetime',
        'api_enabled_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $station) {
            if (empty($station->id)) {
                $station->id = (string) Str::ulid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(LockerPackage::class, 'station_id');
    }

    public function publicBookingUrl(): string
    {
        return url('/locker-point/'.$this->slug);
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key_hash);
    }
}

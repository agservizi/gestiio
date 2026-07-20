<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LockerSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'daily_rate',
        'max_capacity',
        'min_days',
        'max_packages_per_booking',
        'currency',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'max_capacity' => 'integer',
        'min_days' => 'integer',
        'max_packages_per_booking' => 'integer',
    ];

    public static function singleton(): self
    {
        return static::firstOrCreate(
            ['id' => 'default'],
            [
                'daily_rate' => config('locker.default_rate'),
                'max_capacity' => config('locker.max_capacity'),
                'min_days' => config('locker.min_days'),
                'max_packages_per_booking' => config('locker.max_packages_per_booking'),
                'currency' => config('locker.currency'),
            ]
        );
    }
}

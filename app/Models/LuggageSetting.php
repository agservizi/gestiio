<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LuggageSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'daily_rate',
        'max_capacity',
        'min_days',
        'max_bags_per_booking',
        'currency',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'max_capacity' => 'integer',
        'min_days' => 'integer',
        'max_bags_per_booking' => 'integer',
    ];

    public static function singleton(): self
    {
        return static::firstOrCreate(
            ['id' => 'default'],
            [
                'daily_rate' => config('luggage.default_rate'),
                'max_capacity' => config('luggage.max_capacity'),
                'min_days' => config('luggage.min_days'),
                'max_bags_per_booking' => config('luggage.max_bags_per_booking'),
                'currency' => config('luggage.currency'),
            ]
        );
    }
}

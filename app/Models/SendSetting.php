<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SendSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $all = Cache::remember('send_settings_all', 60, function () {
            return self::query()->pluck('value', 'key')->all();
        });

        return $all[$key] ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('send_settings_all');
    }
}

<?php

namespace App\Http\Services;

use App\Models\LuggageSetting;
use App\Models\LuggageStation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LuggageStationService
{
    public function ensureStation(User $user): ?LuggageStation
    {
        if (! Schema::hasTable('luggage_stations')) {
            return null;
        }

        if ($user->hasPermissionTo('admin')) {
            return null;
        }

        $existing = LuggageStation::query()->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $global = LuggageSetting::singleton();
        $name = trim(($user->nome ?? '').' '.($user->cognome ?? ''));
        if ($name === '') {
            $name = $user->alias ?: ('Agente '.$user->id);
        }

        return LuggageStation::create([
            'user_id' => $user->id,
            'slug' => $this->uniqueSlug($user),
            'name' => $name,
            'daily_rate' => $global->daily_rate,
            'currency' => $global->currency,
            'max_capacity' => $global->max_capacity,
            'min_days' => $global->min_days,
            'max_bags_per_booking' => $global->max_bags_per_booking,
            'online_booking_enabled' => false,
            'api_enabled' => false,
        ]);
    }

    public function findBySlug(string $slug): ?LuggageStation
    {
        return LuggageStation::query()->where('slug', $slug)->first();
    }

    public function findByApiKey(string $key): ?LuggageStation
    {
        $hash = hash('sha256', $key);

        return LuggageStation::query()->where('api_key_hash', $hash)->first();
    }

    public function forUser(User $user): ?LuggageStation
    {
        if ($user->hasPermissionTo('admin')) {
            return null;
        }

        return $this->ensureStation($user);
    }

    public function updateStation(LuggageStation $station, array $data): LuggageStation
    {
        $station->fill(array_filter([
            'name' => $data['name'] ?? null,
            'daily_rate' => $data['daily_rate'] ?? null,
            'currency' => isset($data['currency']) ? strtoupper($data['currency']) : null,
            'max_capacity' => $data['max_capacity'] ?? null,
            'min_days' => $data['min_days'] ?? null,
            'max_bags_per_booking' => $data['max_bags_per_booking'] ?? null,
            'online_booking_enabled' => array_key_exists('online_booking_enabled', $data)
                ? filter_var($data['online_booking_enabled'], FILTER_VALIDATE_BOOLEAN)
                : null,
        ], fn ($v) => $v !== null));

        $station->save();

        return $station->fresh();
    }

    public function requestApi(LuggageStation $station): LuggageStation
    {
        $station->update(['api_requested_at' => now()]);

        return $station->fresh();
    }

    /**
     * @return array{station: LuggageStation, plain_key: string}
     */
    public function enableApi(LuggageStation $station): array
    {
        $plain = $this->generatePlainApiKey();
        $station->update([
            'api_enabled' => true,
            'api_key_hash' => hash('sha256', $plain),
            'api_key_prefix' => substr($plain, 0, 8),
            'api_enabled_at' => now(),
            'api_requested_at' => $station->api_requested_at ?? now(),
        ]);

        return ['station' => $station->fresh(), 'plain_key' => $plain];
    }

    /**
     * @return array{station: LuggageStation, plain_key: string}
     */
    public function regenerateApiKey(LuggageStation $station): array
    {
        abort_unless($station->api_enabled, 422, 'API non abilitate per questa postazione.');

        return $this->enableApi($station);
    }

    public function disableApi(LuggageStation $station): LuggageStation
    {
        $station->update([
            'api_enabled' => false,
            'api_key_hash' => null,
            'api_key_prefix' => null,
            'api_enabled_at' => null,
        ]);

        return $station->fresh();
    }

    protected function generatePlainApiKey(): string
    {
        return 'lg_'.Str::lower(Str::random(40));
    }

    protected function uniqueSlug(User $user): string
    {
        $base = Str::slug((string) ($user->alias ?: ($user->nome.'-'.$user->cognome) ?: ('agente-'.$user->id)));
        if ($base === '') {
            $base = 'agente-'.$user->id;
        }

        $slug = $base;
        $i = 2;
        while (LuggageStation::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

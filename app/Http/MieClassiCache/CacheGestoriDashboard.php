<?php

namespace App\Http\MieClassiCache;

use App\Models\Agente;
use App\Models\Gestore;
use Cache;
use Log;

class CacheGestoriDashboard
{
    public const CACHE_KEY = 'CacheGestoriDashboard';

    public static function get($userId)
    {
        if (Cache::has(self::CACHE_KEY)) {
            $values = Cache::get(self::CACHE_KEY);
            if (isset($values[$userId])) {
                return $values[$userId];
            } else {
                return self::creaCache($userId, $values);
            }
        } else {
            return self::creaCache($userId, []);
        }
    }

    public static function forget($userId = null)
    {
        if ($userId == null) {
            Cache::forget(self::CACHE_KEY);
        } else {
            if (Cache::has(self::CACHE_KEY)) {
                $values = Cache::get(self::CACHE_KEY);
                if (isset($values[$userId])) {
                    unset($values[$userId]);
                    Cache::forever(self::CACHE_KEY, $values);
                }
            }
        }
    }

    protected static function creaCache($userId, $values)
    {
        $start = microtime(true);
        $records = Gestore::query()
            ->select('id', 'nome', 'colore_hex', 'url', 'attivo')
            ->where('attivo', 1)
            ->whereNotNull('url')->whereHas('mandati', function ($q) use ($userId) {
                $q->where('agente_id', $userId)->where('attivo', 1);
            })
            ->orderBy('nome')
            ->get();
        $values[$userId] = $records;
        Cache::forever(self::CACHE_KEY, $values);
        Log::debug('Creata cache ' . self::CACHE_KEY . '[' . $userId . '] in ' . number_format(microtime(true) - $start, 3));

        return $records;
    }
}

<?php

namespace App\Http\MieClassiCache;

use App\Http\MieClassi\PuliziaDatabase;
use App\Models\LetturaTicket;
use App\Models\RegistroLogin;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use function App\aslAttiva;

class CacheConteggioTicketsDaLeggere
{


    public static function get($userId)
    {
        if (Cache::has(self::cacheKey($userId))) {
            return Cache::get(self::cacheKey($userId));
        } else {
            return self::creaCache($userId);
        }
    }


    public static function forget($userId)
    {
        Cache::forget(self::cacheKey($userId));

    }

    protected static function creaCache($userId): int
    {
        $start = microtime(true);


        $conteggio = LetturaTicket::where('user_id', $userId)->where('messaggio_letto', 0)->count();


        Log::debug('Creata cache ' . self::cacheKey($userId) . ' in ' . number_format(microtime(true) - $start, 3));
        Cache::forever(self::cacheKey($userId), $conteggio);


        return $conteggio;


    }


    protected static function cacheKey($userId)
    {
        return 'CacheConteggioTicketsDaLeggere' . $userId;
    }


}

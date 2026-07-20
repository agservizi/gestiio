<?php

namespace App\Http\Middleware;

use App\Http\Services\LuggageStationService;
use Closure;
use Illuminate\Http\Request;

class ValidateLuggageApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) $request->header('x-api-key', '');
        $expectedGlobal = (string) config('luggage.api_key');

        if ($key === '') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key mancante o non valida',
                ],
            ], 401);
        }

        if ($expectedGlobal !== '' && hash_equals($expectedGlobal, $key)) {
            $request->attributes->set('luggage_station', null);
            $request->attributes->set('luggage_api_scope', 'hq');

            return $next($request);
        }

        $station = app(LuggageStationService::class)->findByApiKey($key);
        if (! $station) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key mancante o non valida',
                ],
            ], 401);
        }

        if (! $station->api_enabled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'API_DISABLED',
                    'message' => 'API della postazione non abilitate',
                ],
            ], 403);
        }

        $slugHeader = trim((string) $request->header('X-Station-Slug', ''));
        if ($slugHeader !== '' && strcasecmp($slugHeader, $station->slug) !== 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATION_MISMATCH',
                    'message' => 'X-Station-Slug non corrisponde alla postazione della API key',
                ],
            ], 403);
        }

        $request->attributes->set('luggage_station', $station);
        $request->attributes->set('luggage_api_scope', 'station');

        return $next($request);
    }
}

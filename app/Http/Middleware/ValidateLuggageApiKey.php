<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateLuggageApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('luggage.api_key');
        $key = $request->header('x-api-key');

        if (! $expected || ! $key || ! hash_equals((string) $expected, (string) $key)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key mancante o non valida',
                ],
            ], 401);
        }

        return $next($request);
    }
}

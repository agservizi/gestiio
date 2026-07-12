<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LuggageHealthController extends Controller
{
    use RespondsWithLuggageJson;

    public function show(): JsonResponse
    {
        return $this->luggageSuccess([
            'status' => 'ok',
            'service' => 'deposito-bagagli',
            'version' => '1.1.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

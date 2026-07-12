<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LuggageDepositResource;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LuggageVerifyController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $token = $request->query('token');
        if (! $token) {
            return $this->luggageError('MISSING_TOKEN', 'Parametro token mancante', 400);
        }

        $deposit = $this->service->verifyByToken($token);
        if (! $deposit) {
            return $this->luggageError('INVALID_TOKEN', 'Token non valido', 404);
        }

        return $this->luggageSuccess((new LuggageDepositResource($deposit))->resolve());
    }
}

<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LuggageAvailabilityController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $dateStr = $request->query('date');
        if (! $dateStr) {
            return $this->luggageError('MISSING_DATE', 'Parametro date obbligatorio (YYYY-MM-DD)', 400);
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable) {
            return $this->luggageError('INVALID_DATE', 'Formato data non valido', 400);
        }

        return $this->luggageSuccess($this->service->getAvailability($date));
    }
}

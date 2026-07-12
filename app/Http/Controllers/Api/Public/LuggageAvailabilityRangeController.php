<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LuggageAvailabilityRangeController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $fromStr = $request->query('from');
        $toStr = $request->query('to');

        if (! $fromStr || ! $toStr) {
            return $this->luggageError('MISSING_RANGE', 'Parametri from e to obbligatori (YYYY-MM-DD)', 400);
        }

        try {
            $from = Carbon::parse($fromStr)->startOfDay();
            $to = Carbon::parse($toStr)->startOfDay();
        } catch (\Throwable) {
            return $this->luggageError('INVALID_DATE', 'Formato data non valido', 400);
        }

        if ($to->lt($from)) {
            return $this->luggageError('INVALID_RANGE', 'La data to deve essere >= from', 400);
        }

        if ($from->diffInDays($to) > 60) {
            return $this->luggageError('RANGE_TOO_LARGE', 'Intervallo massimo 60 giorni', 400);
        }

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $days[] = $this->service->getAvailability($day->copy());
        }

        return $this->luggageSuccess($days, 200, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'count' => count($days),
        ]);
    }
}

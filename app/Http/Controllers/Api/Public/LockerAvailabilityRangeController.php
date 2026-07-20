<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ResolvesLockerStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LockerAvailabilityRangeController extends Controller
{
    use RespondsWithLockerJson;
    use ResolvesLockerStationFromRequest;

    public function __construct(private LockerPackageService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $fromStr = $request->query('from');
        $toStr = $request->query('to');

        if (! $fromStr || ! $toStr) {
            return $this->lockerError('MISSING_RANGE', 'Parametri from e to obbligatori (YYYY-MM-DD)', 400);
        }

        try {
            $from = Carbon::parse($fromStr)->startOfDay();
            $to = Carbon::parse($toStr)->startOfDay();
        } catch (\Throwable) {
            return $this->lockerError('INVALID_DATE', 'Formato data non valido', 400);
        }

        if ($to->lt($from)) {
            return $this->lockerError('INVALID_RANGE', 'to deve essere >= from', 400);
        }

        if ($from->diffInDays($to) > 31) {
            return $this->lockerError('RANGE_TOO_LARGE', 'Intervallo massimo 31 giorni', 400);
        }

        $station = $this->lockerStation($request);
        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $this->service->getAvailability($d->copy(), $station);
        }

        return $this->lockerSuccess(['days' => $days]);
    }
}

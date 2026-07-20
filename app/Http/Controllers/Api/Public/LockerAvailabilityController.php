<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ResolvesLockerStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LockerAvailabilityController extends Controller
{
    use RespondsWithLockerJson;
    use ResolvesLockerStationFromRequest;

    public function __construct(private LockerPackageService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $dateStr = $request->query('date');
        if (! $dateStr) {
            return $this->lockerError('MISSING_DATE', 'Parametro date obbligatorio (YYYY-MM-DD)', 400);
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable) {
            return $this->lockerError('INVALID_DATE', 'Formato data non valido', 400);
        }

        return $this->lockerSuccess($this->service->getAvailability($date, $this->lockerStation($request)));
    }
}

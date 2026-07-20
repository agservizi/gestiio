<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ResolvesLockerStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use App\Http\Support\LockerConfig;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerPricingController extends Controller
{
    use RespondsWithLockerJson;
    use ResolvesLockerStationFromRequest;

    public function __construct(private LockerPackageService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $station = $this->lockerStation($request);
        $settings = $this->service->settingsFor($station);

        return $this->lockerSuccess([
            'dailyRate' => (float) $settings->daily_rate,
            'currency' => $settings->currency,
            'minDays' => $settings->min_days,
            'maxPackagesPerBooking' => $settings->max_packages_per_booking,
            'maxDailyCapacity' => $settings->max_capacity,
            'onlineIntakeEnabled' => $station
                ? (bool) $station->online_intake_enabled
                : LockerConfig::onlineIntakeEnabled(),
            'bookingInstructions' => LockerConfig::bookingInstructions(),
            'stationSlug' => $station?->slug,
            'pricingNote' => 'Tariffa giornaliera per pacco. Giorni parziali conteggiati come giorno intero.',
        ]);
    }
}

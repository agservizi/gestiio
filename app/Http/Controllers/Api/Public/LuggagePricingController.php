<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ResolvesLuggageStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Support\LuggageConfig;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LuggagePricingController extends Controller
{
    use RespondsWithLuggageJson;
    use ResolvesLuggageStationFromRequest;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $station = $this->luggageStation($request);
        $settings = $this->service->settingsFor($station);

        return $this->luggageSuccess([
            'dailyRate' => (float) $settings->daily_rate,
            'currency' => $settings->currency,
            'minDays' => $settings->min_days,
            'maxBagsPerBooking' => $settings->max_bags_per_booking,
            'maxDailyCapacity' => $settings->max_capacity,
            'onlineBookingEnabled' => $station
                ? (bool) $station->online_booking_enabled
                : LuggageConfig::onlineBookingEnabled(),
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
            'stationSlug' => $station?->slug,
            'pricingNote' => 'Tariffa giornaliera per bagaglio. Giorni parziali conteggiati come giorno intero.',
        ]);
    }
}

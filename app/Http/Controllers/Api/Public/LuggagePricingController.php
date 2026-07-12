<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Support\LuggageConfig;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;

class LuggagePricingController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(): JsonResponse
    {
        $settings = $this->service->getSettings();

        return $this->luggageSuccess([
            'dailyRate' => (float) $settings->daily_rate,
            'currency' => $settings->currency,
            'minDays' => $settings->min_days,
            'maxBagsPerBooking' => $settings->max_bags_per_booking,
            'maxDailyCapacity' => $settings->max_capacity,
            'onlineBookingEnabled' => LuggageConfig::onlineBookingEnabled(),
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
            'pricingNote' => 'Tariffa giornaliera per bagaglio. Giorni parziali conteggiati come giorno intero.',
        ]);
    }
}

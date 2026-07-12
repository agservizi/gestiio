<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Support\LuggageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLuggageSettingsRequest;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;

class LuggageSettingsController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(): JsonResponse
    {
        $this->authorize('manageSettings', \App\Models\LuggageDeposit::class);

        $settings = $this->service->getSettings();

        return $this->luggageSuccess([
            'dailyRate' => (float) $settings->daily_rate,
            'maxCapacity' => $settings->max_capacity,
            'minDays' => $settings->min_days,
            'maxBagsPerBooking' => $settings->max_bags_per_booking,
            'currency' => $settings->currency,
            'maxDailyCapacity' => $settings->max_capacity,
            'onlineBookingEnabled' => LuggageConfig::onlineBookingEnabled(),
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
        ]);
    }

    public function update(UpdateLuggageSettingsRequest $request): JsonResponse
    {
        $this->authorize('manageSettings', \App\Models\LuggageDeposit::class);

        $settings = $this->service->applySettingsUpdate($request->validated());

        return $this->luggageSuccess([
            'dailyRate' => (float) $settings->daily_rate,
            'maxCapacity' => $settings->max_capacity,
            'minDays' => $settings->min_days,
            'maxBagsPerBooking' => $settings->max_bags_per_booking,
            'currency' => $settings->currency,
            'maxDailyCapacity' => $settings->max_capacity,
            'onlineBookingEnabled' => LuggageConfig::onlineBookingEnabled(),
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
        ]);
    }
}

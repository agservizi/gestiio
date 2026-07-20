<?php

namespace App\Http\Controllers\Api;

use App\Models\LuggageStation;
use Illuminate\Http\Request;

trait ResolvesLuggageStationFromRequest
{
    protected function luggageStation(Request $request): ?LuggageStation
    {
        $station = $request->attributes->get('luggage_station');

        return $station instanceof LuggageStation ? $station : null;
    }

    protected function assertDepositBelongsToApiScope($deposit, Request $request): bool
    {
        $station = $this->luggageStation($request);
        if ($station) {
            return $deposit && $deposit->station_id === $station->id;
        }

        return $deposit && $deposit->station_id === null;
    }
}

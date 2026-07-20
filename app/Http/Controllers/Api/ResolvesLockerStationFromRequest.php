<?php

namespace App\Http\Controllers\Api;

use App\Models\LockerStation;
use Illuminate\Http\Request;

trait ResolvesLockerStationFromRequest
{
    protected function lockerStation(Request $request): ?LockerStation
    {
        $station = $request->attributes->get('locker_station');

        return $station instanceof LockerStation ? $station : null;
    }

    protected function assertPackageBelongsToApiScope($package, Request $request): bool
    {
        $station = $this->lockerStation($request);
        if ($station) {
            return $package && $package->station_id === $station->id;
        }

        return $package && $package->station_id === null;
    }
}

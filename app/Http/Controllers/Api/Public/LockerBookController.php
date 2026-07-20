<?php

namespace App\Http\Controllers\Api\Public;

use App\Exceptions\LockerNoAvailabilityException;
use App\Http\Controllers\Api\ResolvesLockerStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLockerBookingRequest;
use App\Http\Resources\LockerPackageResource;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class LockerBookController extends Controller
{
    use RespondsWithLockerJson;
    use ResolvesLockerStationFromRequest;

    public function __construct(private LockerPackageService $service)
    {
    }

    public function store(StoreLockerBookingRequest $request): JsonResponse
    {
        try {
            $package = $this->service->create(
                $request->payload(),
                'api',
                $this->lockerStation($request)
            );

            return $this->lockerSuccess(
                (new LockerPackageResource($package))->resolve(),
                201
            );
        } catch (LockerNoAvailabilityException $e) {
            return $this->lockerError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'online') ? 'BOOKING_DISABLED' : 'VALIDATION_ERROR';

            return $this->lockerError($code, $e->getMessage(), $code === 'BOOKING_DISABLED' ? 403 : 400);
        }
    }
}

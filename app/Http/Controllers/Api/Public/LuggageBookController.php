<?php

namespace App\Http\Controllers\Api\Public;

use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Api\ResolvesLuggageStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLuggageBookingRequest;
use App\Http\Resources\LuggageDepositResource;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggageBookController extends Controller
{
    use RespondsWithLuggageJson;
    use ResolvesLuggageStationFromRequest;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function store(StoreLuggageBookingRequest $request): JsonResponse
    {
        try {
            $deposit = $this->service->create(
                $request->payload(),
                'PORTALE',
                $this->luggageStation($request)
            );

            return $this->luggageSuccess(
                (new LuggageDepositResource($deposit))->resolve(),
                201
            );
        } catch (LuggageNoAvailabilityException $e) {
            return $this->luggageError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'online') ? 'BOOKING_DISABLED' : 'VALIDATION_ERROR';

            return $this->luggageError($code, $e->getMessage(), $code === 'BOOKING_DISABLED' ? 403 : 400);
        }
    }
}

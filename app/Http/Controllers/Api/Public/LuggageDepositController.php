<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\LuggageDepositStatus;
use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Api\ResolvesLuggageStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LuggageDepositResource;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggageDepositController extends Controller
{
    use RespondsWithLuggageJson;
    use ResolvesLuggageStationFromRequest;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->only([
                'email', 'code', 'status', 'from', 'to', 'q',
            ]),
            (int) $request->get('page', 1),
            (int) $request->get('limit', 20),
            $this->luggageStation($request),
            false
        );

        return $this->luggageSuccess(
            LuggageDepositResource::collection($paginator->items())->resolve(),
            200,
            [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $deposit = $this->service->findByCode($code);
        if (! $this->assertDepositBelongsToApiScope($deposit, $request)) {
            return $this->notFound();
        }

        return $this->luggageSuccess((new LuggageDepositResource($deposit))->resolve());
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $deposit = $this->service->findByCode($code);
        if (! $this->assertDepositBelongsToApiScope($deposit, $request)) {
            return $this->notFound();
        }

        if ($deposit->status !== LuggageDepositStatus::PRENOTATO) {
            return $this->luggageError('NOT_EDITABLE', 'Modifica consentita solo per PRENOTATO', 409);
        }

        $validated = $request->validate([
            'customerName' => ['sometimes', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
            'bagCount' => ['nullable', 'integer', 'min:1'],
            'bookingDate' => ['sometimes', 'date'],
            'expectedCheckIn' => ['nullable', 'date'],
            'expectedCheckOut' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $updated = $this->service->updatePrenotato($deposit, array_filter([
                'customer_name' => $validated['customerName'] ?? null,
                'customer_email' => array_key_exists('customerEmail', $validated) ? $validated['customerEmail'] : null,
                'customer_phone' => array_key_exists('customerPhone', $validated) ? $validated['customerPhone'] : null,
                'bag_count' => $validated['bagCount'] ?? null,
                'booking_date' => $validated['bookingDate'] ?? null,
                'expected_check_in' => array_key_exists('expectedCheckIn', $validated) ? $validated['expectedCheckIn'] : null,
                'expected_check_out' => array_key_exists('expectedCheckOut', $validated) ? $validated['expectedCheckOut'] : null,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : null,
            ], fn ($v) => $v !== null));
        } catch (LuggageNoAvailabilityException $e) {
            return $this->luggageError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->luggageError('NOT_EDITABLE', $e->getMessage(), 409);
        }

        return $this->luggageSuccess((new LuggageDepositResource($updated))->resolve());
    }

    public function cancel(Request $request, string $code): JsonResponse
    {
        $deposit = $this->service->findByCode($code);
        if (! $this->assertDepositBelongsToApiScope($deposit, $request)) {
            return $this->notFound();
        }

        if ($deposit->status !== LuggageDepositStatus::PRENOTATO) {
            return $this->luggageError('NOT_CANCELLABLE', 'Cancellazione consentita solo per PRENOTATO', 409);
        }

        try {
            $cancelled = $this->service->cancel($deposit);
        } catch (InvalidArgumentException $e) {
            return $this->luggageError('NOT_CANCELLABLE', $e->getMessage(), 409);
        }

        return $this->luggageSuccess([
            'id' => $cancelled->id,
            'code' => $cancelled->code,
            'status' => $cancelled->status->value,
        ]);
    }

    private function notFound(): JsonResponse
    {
        return $this->luggageError('DEPOSIT_NOT_FOUND', 'Deposito non trovato', 404);
    }
}

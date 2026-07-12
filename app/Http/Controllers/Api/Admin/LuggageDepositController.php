<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LuggageDepositResource;
use App\Http\Services\LuggageDepositService;
use App\Models\LuggageDeposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggageDepositController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LuggageDeposit::class);

        $paginator = $this->service->list(
            $request->only(['view', 'q', 'status', 'from', 'to', 'source']),
            (int) $request->get('page', 1),
            (int) $request->get('limit', 25)
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

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', LuggageDeposit::class);

        $validated = $request->validate([
            'customer_name' => ['required', 'string'],
            'booking_date' => ['required', 'date'],
            'bag_count' => ['nullable', 'integer', 'min:1'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'cliente_id' => ['nullable', 'integer', 'exists:clienti,id'],
        ]);

        try {
            $deposit = $this->service->create($validated, 'SPORTELLO');
        } catch (LuggageNoAvailabilityException $e) {
            return $this->luggageError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->luggageError('VALIDATION_ERROR', $e->getMessage(), 400);
        }

        return $this->luggageSuccess(
            (new LuggageDepositResource($deposit))->resolve(),
            201
        );
    }

    public function show(LuggageDeposit $deposit): JsonResponse
    {
        $this->authorize('view', $deposit);

        return $this->luggageSuccess((new LuggageDepositResource($deposit))->resolve());
    }

    public function update(Request $request, LuggageDeposit $deposit): JsonResponse
    {
        $this->authorize('update', $deposit);

        $validated = $request->validate([
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'bag_count' => ['nullable', 'integer', 'min:1'],
            'booking_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'cliente_id' => ['nullable', 'integer', 'exists:clienti,id'],
        ]);

        try {
            $updated = $this->service->updateStaffDeposit($deposit, $validated);
        } catch (LuggageNoAvailabilityException $e) {
            return $this->luggageError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->luggageError('NOT_EDITABLE', $e->getMessage(), 409);
        }

        return $this->luggageSuccess((new LuggageDepositResource($updated))->resolve());
    }

    public function destroy(LuggageDeposit $deposit): JsonResponse
    {
        $this->authorize('delete', $deposit);

        $deposit->delete();

        return $this->luggageSuccess(['deleted' => true], 200);
    }
}

<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\LockerPackageStatus;
use App\Exceptions\LockerNoAvailabilityException;
use App\Http\Controllers\Api\ResolvesLockerStationFromRequest;
use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LockerPackageResource;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LockerPackageController extends Controller
{
    use RespondsWithLockerJson;
    use ResolvesLockerStationFromRequest;

    public function __construct(private LockerPackageService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->only(['code', 'status', 'from', 'to', 'q']),
            (int) $request->get('page', 1),
            (int) $request->get('limit', 20),
            $this->lockerStation($request),
            false
        );

        return $this->lockerSuccess(
            LockerPackageResource::collection($paginator->items())->resolve(),
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
        $package = $this->service->findByCode($code);
        if (! $this->assertPackageBelongsToApiScope($package, $request)) {
            return $this->notFound();
        }

        return $this->lockerSuccess((new LockerPackageResource($package))->resolve());
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $package = $this->service->findByCode($code);
        if (! $this->assertPackageBelongsToApiScope($package, $request)) {
            return $this->notFound();
        }

        if ($package->status !== LockerPackageStatus::PRENOTATO) {
            return $this->lockerError('NOT_EDITABLE', 'Modifica consentita solo per PRENOTATO', 409);
        }

        $validated = $request->validate([
            'recipientName' => ['sometimes', 'string', 'max:255'],
            'recipientEmail' => ['nullable', 'email', 'max:255'],
            'recipientPhone' => ['nullable', 'string', 'max:50'],
            'senderName' => ['nullable', 'string', 'max:255'],
            'senderPhone' => ['nullable', 'string', 'max:50'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'trackingCode' => ['nullable', 'string', 'max:100'],
            'expectedPickupDate' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $updated = $this->service->updatePrenotato($package, array_filter([
                'recipient_name' => $validated['recipientName'] ?? null,
                'recipient_email' => array_key_exists('recipientEmail', $validated) ? $validated['recipientEmail'] : null,
                'recipient_phone' => array_key_exists('recipientPhone', $validated) ? $validated['recipientPhone'] : null,
                'sender_name' => array_key_exists('senderName', $validated) ? $validated['senderName'] : null,
                'sender_phone' => array_key_exists('senderPhone', $validated) ? $validated['senderPhone'] : null,
                'carrier' => array_key_exists('carrier', $validated) ? $validated['carrier'] : null,
                'tracking_code' => array_key_exists('trackingCode', $validated) ? $validated['trackingCode'] : null,
                'expected_pickup_date' => $validated['expectedPickupDate'] ?? null,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : null,
            ], fn ($v) => $v !== null));
        } catch (LockerNoAvailabilityException $e) {
            return $this->lockerError('NO_AVAILABILITY', $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->lockerError('NOT_EDITABLE', $e->getMessage(), 409);
        }

        return $this->lockerSuccess((new LockerPackageResource($updated))->resolve());
    }

    public function cancel(Request $request, string $code): JsonResponse
    {
        $package = $this->service->findByCode($code);
        if (! $this->assertPackageBelongsToApiScope($package, $request)) {
            return $this->notFound();
        }

        if ($package->status !== LockerPackageStatus::PRENOTATO) {
            return $this->lockerError('NOT_CANCELLABLE', 'Cancellazione consentita solo per PRENOTATO', 409);
        }

        try {
            $cancelled = $this->service->cancel($package);
        } catch (InvalidArgumentException $e) {
            return $this->lockerError('NOT_CANCELLABLE', $e->getMessage(), 409);
        }

        return $this->lockerSuccess([
            'id' => $cancelled->id,
            'code' => $cancelled->code,
            'status' => $cancelled->status->value,
        ]);
    }

    private function notFound(): JsonResponse
    {
        return $this->lockerError('PACKAGE_NOT_FOUND', 'Pacco non trovato', 404);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\LuggageDepositActionRequest;
use App\Http\Resources\LuggageDepositResource;
use App\Http\Services\LuggageDepositService;
use App\Models\LuggageDeposit;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class LuggageDepositActionController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(private LuggageDepositService $service)
    {
    }

    public function handle(LuggageDepositActionRequest $request, LuggageDeposit $deposit): JsonResponse
    {
        $this->authorize('update', $deposit);

        try {
            $result = match ($request->input('action')) {
                'check-in' => [
                    'deposit' => $this->service->checkIn($deposit, $request->input('bagTags')),
                ],
                'check-out' => $this->service->checkOut(
                    $deposit,
                    $request->input('paymentMethod', 'Contanti')
                ),
                'cancel' => [
                    'deposit' => $this->service->cancel($deposit),
                ],
                'no-show' => [
                    'deposit' => $this->service->markNoShow($deposit),
                ],
                default => throw new InvalidArgumentException('Azione non supportata'),
            };

            $data = isset($result['deposit'])
                ? (new LuggageDepositResource($result['deposit']))->resolve()
                : $result;

            if (isset($result['pricing'])) {
                $data['pricing'] = $result['pricing'];
            }

            return $this->luggageSuccess($data);
        } catch (InvalidArgumentException $e) {
            return $this->luggageError('INVALID_ACTION', $e->getMessage(), 409);
        }
    }
}

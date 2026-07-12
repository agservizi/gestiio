<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\LuggageDepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggagePickupController extends Controller
{
    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(string $id, Request $request)
    {
        $token = (string) $request->query('t');
        abort_unless($token !== '', 404);

        $deposit = $this->service->findForPickup($id, $token);
        abort_unless($deposit, 404);

        if ($deposit->status === LuggageDepositStatus::COMPLETATO) {
            return view('Frontend.LuggageDeposit.pickup-done', [
                'deposit' => $deposit,
            ]);
        }

        abort_unless($deposit->status === LuggageDepositStatus::CHECK_IN, 404);

        $progress = $this->service->getPickupProgress($deposit);

        return view('Frontend.LuggageDeposit.pickup', [
            'deposit' => $deposit,
            'token' => $token,
            'expectedTags' => $progress['expected'],
            'scannedTags' => $progress['scanned'],
            'pricing' => $progress['pricing'],
        ]);
    }

    public function scan(string $id, Request $request): JsonResponse
    {
        $token = (string) $request->query('t');
        if ($token === '') {
            return response()->json(['success' => false, 'error' => ['message' => 'Token mancante']], 400);
        }

        $deposit = $this->service->findForPickup($id, $token);
        if (! $deposit) {
            return response()->json(['success' => false, 'error' => ['message' => 'Deposito non trovato']], 404);
        }

        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:50'],
        ]);

        try {
            $result = $this->service->scanPickupTag($deposit, $validated['tag']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => ['message' => $e->getMessage()]], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function complete(string $id, Request $request): JsonResponse
    {
        $token = (string) $request->query('t');
        if ($token === '') {
            return response()->json(['success' => false, 'error' => ['message' => 'Token mancante']], 400);
        }

        $deposit = $this->service->findForPickup($id, $token);
        if (! $deposit) {
            return response()->json(['success' => false, 'error' => ['message' => 'Deposito non trovato']], 404);
        }

        $validated = $request->validate([
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'scannedTags' => ['required', 'array', 'min:1'],
            'scannedTags.*' => ['string', 'max:50'],
        ]);

        try {
            $result = $this->service->completePickup(
                $deposit,
                $validated['paymentMethod'] ?? 'Contanti',
                $validated['scannedTags']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => ['message' => $e->getMessage()]], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $result['deposit']->code,
                'totalAmount' => $result['pricing']['total'],
                'days' => $result['pricing']['days'],
                'paymentMethod' => $result['deposit']->payment_method,
            ],
        ]);
    }
}

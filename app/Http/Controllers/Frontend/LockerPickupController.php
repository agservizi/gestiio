<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\LockerPackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Services\LockerPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LockerPickupController extends Controller
{
    public function __construct(private LockerPackageService $service)
    {
    }

    public function show(string $id, Request $request)
    {
        $token = (string) $request->query('t');
        abort_unless($token !== '', 404);

        $package = $this->service->findForPickup($id, $token);
        abort_unless($package, 404);

        if ($package->status === LockerPackageStatus::CONSEGNATO) {
            return view('Frontend.LockerPoint.pickup-done', [
                'package' => $package,
            ]);
        }

        if ($package->status !== LockerPackageStatus::IN_GIACENZA) {
            return view('Frontend.LockerPoint.pickup-waiting', [
                'package' => $package,
                'token' => $token,
            ]);
        }

        $progress = $this->service->getPickupProgress($package);

        return view('Frontend.LockerPoint.pickup', [
            'package' => $package,
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

        $package = $this->service->findForPickup($id, $token);
        if (! $package) {
            return response()->json(['success' => false, 'error' => ['message' => 'Pacco non trovato']], 404);
        }

        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:50'],
        ]);

        try {
            $result = $this->service->scanPickupTag($package, $validated['tag']);
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

        $package = $this->service->findForPickup($id, $token);
        if (! $package) {
            return response()->json(['success' => false, 'error' => ['message' => 'Pacco non trovato']], 404);
        }

        $validated = $request->validate([
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'scannedTags' => ['required', 'array', 'min:1'],
            'scannedTags.*' => ['string', 'max:50'],
            'signature' => ['required', 'string'],
            'signerName' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->service->completePickup(
                $package,
                $validated['paymentMethod'] ?? 'Contanti',
                $validated['scannedTags'],
                $validated['signature'],
                $validated['signerName']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => ['message' => $e->getMessage()]], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $result['package']->code,
                'totalAmount' => $result['pricing']['total'],
                'days' => $result['pricing']['days'],
                'paymentMethod' => $result['package']->payment_method,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLuggageBookingRequest;
use App\Http\Services\LuggageDepositService;
use App\Http\Support\LuggageConfig;
use App\Http\Support\LuggageQrCode;
use App\Models\LuggageDeposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggageBookingController extends Controller
{
    public function __construct(private LuggageDepositService $service)
    {
    }

    public function index()
    {
        $settings = $this->service->getSettings();
        $availability = $this->service->getAvailability(today());

        return view('Frontend.LuggageDeposit.book', [
            'settings' => $settings,
            'availability' => $availability,
            'onlineBookingEnabled' => LuggageConfig::onlineBookingEnabled(),
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
            'metaTitle' => 'Deposito Bagagli | Prenota online',
            'metaDescription' => 'Prenota il deposito bagagli online con conferma immediata.',
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->getAvailability(\Illuminate\Support\Carbon::parse($validated['date'])),
        ]);
    }

    public function store(StoreLuggageBookingRequest $request)
    {
        try {
            $deposit = $this->service->create($request->payload(), 'PORTALE');
        } catch (LuggageNoAvailabilityException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'NO_AVAILABILITY', 'message' => $e->getMessage()],
                ], 409);
            }

            return redirect()->back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'BOOKING_DISABLED', 'message' => $e->getMessage()],
                ], 403);
            }

            return redirect()->back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => ['code' => $deposit->code],
            ], 201);
        }

        return redirect()->to(url('/deposito-bagagli/conferma').'?code='.urlencode($deposit->code));
    }

    public function confirm(Request $request)
    {
        $code = $request->query('code');
        abort_unless($code, 404);

        $deposit = $this->service->findByCode($code);
        abort_unless($deposit, 404);

        return view('Frontend.LuggageDeposit.confirm', [
            'deposit' => $deposit,
            'qrSvg' => LuggageQrCode::svg($deposit->verifyUrl(), 220),
        ]);
    }

    public function qr(string $id, Request $request)
    {
        $deposit = LuggageDeposit::findOrFail($id);
        abort_unless($request->query('t') === $deposit->qr_token, 404);

        $target = $request->query('purpose') === 'pickup'
            ? $deposit->pickupUrl()
            : $deposit->verifyUrl();

        return response(LuggageQrCode::svg($target, (int) $request->query('size', 180)), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

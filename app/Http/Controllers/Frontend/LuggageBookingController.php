<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLuggageBookingRequest;
use App\Http\Services\LuggageDepositService;
use App\Http\Services\LuggageStationService;
use App\Http\Support\LuggageConfig;
use App\Http\Support\LuggageQrCode;
use App\Models\LuggageDeposit;
use App\Models\LuggageStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LuggageBookingController extends Controller
{
    public function __construct(
        private LuggageDepositService $service,
        private LuggageStationService $stations,
    ) {
    }

    public function index(?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);
        $settings = $this->service->settingsFor($station);
        $availability = $this->service->getAvailability(today(), $station);
        $onlineEnabled = $station
            ? (bool) $station->online_booking_enabled
            : LuggageConfig::onlineBookingEnabled();

        return view('Frontend.LuggageDeposit.book', [
            'settings' => $settings,
            'availability' => $availability,
            'station' => $station,
            'stationSlug' => $station?->slug,
            'onlineBookingEnabled' => $onlineEnabled,
            'bookingInstructions' => LuggageConfig::bookingInstructions(),
            'metaTitle' => $station
                ? ('Deposito Bagagli '.$station->name.' | Prenota online')
                : 'Deposito Bagagli | Prenota online',
            'metaDescription' => 'Prenota il deposito bagagli online con conferma immediata.',
            'bookAction' => $station
                ? url('/deposito-bagagli/'.$station->slug.'/prenota')
                : url('/deposito-bagagli/prenota'),
            'availabilityUrl' => $station
                ? url('/deposito-bagagli/'.$station->slug.'/disponibilita')
                : url('/deposito-bagagli/disponibilita'),
            'confirmBaseUrl' => $station
                ? url('/deposito-bagagli/'.$station->slug.'/conferma')
                : url('/deposito-bagagli/conferma'),
        ]);
    }

    public function availability(Request $request, ?string $slug = null): JsonResponse
    {
        $station = $this->resolveStationOrAbort($slug);
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->getAvailability(\Illuminate\Support\Carbon::parse($validated['date']), $station),
        ]);
    }

    public function store(StoreLuggageBookingRequest $request, ?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);

        try {
            $deposit = $this->service->create($request->payload(), 'PORTALE', $station);
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

        $confirmUrl = $station
            ? url('/deposito-bagagli/'.$station->slug.'/conferma').'?code='.urlencode($deposit->code)
            : url('/deposito-bagagli/conferma').'?code='.urlencode($deposit->code);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => ['code' => $deposit->code, 'confirmUrl' => $confirmUrl],
            ], 201);
        }

        return redirect()->to($confirmUrl);
    }

    public function confirm(Request $request, ?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);
        $code = $request->query('code');
        abort_unless($code, 404);

        $deposit = $this->service->findByCode($code);
        abort_unless($deposit, 404);

        if ($station) {
            abort_unless($deposit->station_id === $station->id, 404);
        } else {
            abort_unless($deposit->station_id === null, 404);
        }

        return view('Frontend.LuggageDeposit.confirm', [
            'deposit' => $deposit,
            'station' => $station,
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

    protected function resolveStationOrAbort(?string $slug): ?LuggageStation
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $station = $this->stations->findBySlug($slug);
        abort_unless($station, 404);

        return $station;
    }
}

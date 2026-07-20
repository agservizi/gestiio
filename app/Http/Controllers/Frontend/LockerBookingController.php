<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\LockerNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLockerBookingRequest;
use App\Http\Services\LockerPackageService;
use App\Http\Services\LockerStationService;
use App\Http\Support\LockerConfig;
use App\Http\Support\LuggageQrCode;
use App\Models\LockerPackage;
use App\Models\LockerStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LockerBookingController extends Controller
{
    public function __construct(
        private LockerPackageService $service,
        private LockerStationService $stations,
    ) {
    }

    public function index(?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);
        $settings = $this->service->settingsFor($station);
        $availability = $this->service->getAvailability(today(), $station);
        $onlineEnabled = $station
            ? (bool) $station->online_intake_enabled
            : LockerConfig::onlineIntakeEnabled();

        return view('Frontend.LockerPoint.book', [
            'settings' => $settings,
            'availability' => $availability,
            'station' => $station,
            'stationSlug' => $station?->slug,
            'onlineIntakeEnabled' => $onlineEnabled,
            'bookingInstructions' => LockerConfig::bookingInstructions(),
            'metaTitle' => $station
                ? ('Locker Point '.$station->name.' | Prenota ritiro pacco')
                : 'Locker Point | Prenota ritiro pacco',
            'metaDescription' => 'Prenota il ritiro pacco online con conferma immediata.',
            'bookAction' => $station
                ? url('/locker-point/'.$station->slug.'/prenota')
                : url('/locker-point/prenota'),
            'availabilityUrl' => $station
                ? url('/locker-point/'.$station->slug.'/disponibilita')
                : url('/locker-point/disponibilita'),
            'confirmBaseUrl' => $station
                ? url('/locker-point/'.$station->slug.'/conferma')
                : url('/locker-point/conferma'),
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

    public function store(StoreLockerBookingRequest $request, ?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);

        try {
            $package = $this->service->create($request->payload(), 'online', $station);
        } catch (LockerNoAvailabilityException $e) {
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
            ? url('/locker-point/'.$station->slug.'/conferma').'?code='.urlencode($package->code)
            : url('/locker-point/conferma').'?code='.urlencode($package->code);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => ['code' => $package->code, 'confirmUrl' => $confirmUrl],
            ], 201);
        }

        return redirect()->to($confirmUrl);
    }

    public function confirm(Request $request, ?string $slug = null)
    {
        $station = $this->resolveStationOrAbort($slug);
        $code = $request->query('code');
        abort_unless($code, 404);

        $package = $this->service->findByCode($code);
        abort_unless($package, 404);

        if ($station) {
            abort_unless($package->station_id === $station->id, 404);
        } else {
            abort_unless($package->station_id === null, 404);
        }

        return view('Frontend.LockerPoint.confirm', [
            'package' => $package,
            'station' => $station,
            'qrSvg' => LuggageQrCode::svg($package->pickupUrl(), 220),
        ]);
    }

    protected function resolveStationOrAbort(?string $slug): ?LockerStation
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $station = $this->stations->findBySlug($slug);
        abort_unless($station, 404);

        return $station;
    }
}

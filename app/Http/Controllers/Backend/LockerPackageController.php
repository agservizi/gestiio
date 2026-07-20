<?php

namespace App\Http\Controllers\Backend;

use App\Enums\LockerPackageStatus;
use App\Exceptions\LockerNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LockerPackageActionRequest;
use App\Http\Requests\UpdateLockerSettingsRequest;
use App\Http\Services\LockerPackageService;
use App\Http\Services\LockerStationService;
use App\Http\Support\LockerConfig;
use App\Http\Support\LockerTagPdf;
use App\Models\LockerPackage;
use App\Models\LockerStation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class LockerPackageController extends Controller
{
    public function __construct(
        private LockerPackageService $service,
        private LockerStationService $stations,
    ) {
        $this->middleware('can:viewAny,'.LockerPackage::class);
    }

    protected function currentStation(): ?LockerStation
    {
        $user = Auth::user();
        if (! $user || $user->hasPermissionTo('admin')) {
            return null;
        }

        return $this->stations->forUser($user);
    }

    protected function isAdmin(): bool
    {
        return Auth::user()?->hasPermissionTo('admin') ?? false;
    }

    public function dashboard()
    {
        $this->authorize('viewAny', LockerPackage::class);
        $station = $this->currentStation();
        $stats = $this->service->stats(null, null, $station, $this->isAdmin());

        return view('Backend.LockerPoint.dashboard', [
            'stats' => $stats,
            'station' => $station,
            'titoloPagina' => $station ? ('Locker Point — '.$station->name) : 'Locker Point',
            'controller' => self::class,
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', LockerPackage::class);
        $station = $this->currentStation();

        $records = $this->service->list(
            array_merge($request->only(['view', 'q', 'status', 'source']), ['view' => $request->get('view', 'oggi')]),
            (int) $request->get('page', 1),
            (int) config('configurazione.paginazione', 25),
            $station,
            $this->isAdmin()
        );
        $records->appends($request->query());

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.LockerPoint.tabella', [
                    'records' => $records,
                    'controller' => self::class,
                ])->render()),
            ];
        }

        return view('Backend.LockerPoint.index', [
            'records' => $records,
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Elenco '.LockerPackage::NOME_PLURALE,
            'view' => $request->get('view', 'oggi'),
            'testoNuovo' => 'Nuovo '.LockerPackage::NOME_SINGOLARE,
            'testoCerca' => 'Cerca codice o destinatario',
        ]);
    }

    public function pipeline()
    {
        $station = $this->currentStation();
        $base = LockerPackage::query();
        $this->service->scopeStationQuery($base, $station, $this->isAdmin());

        $columns = [
            'prenotati' => (clone $base)->whereIn('status', [LockerPackageStatus::PRENOTATO, LockerPackageStatus::NO_SHOW])->orderBy('expected_pickup_date')->get(),
            'giacenza' => (clone $base)->where('status', LockerPackageStatus::IN_GIACENZA)->orderBy('received_at')->get(),
            'consegnati' => (clone $base)->where('status', LockerPackageStatus::CONSEGNATO)->latest('delivered_at')->limit(20)->get(),
        ];

        return view('Backend.LockerPoint.pipeline', [
            'columns' => $columns,
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Pipeline Locker Point',
        ]);
    }

    public function intakePage(Request $request)
    {
        $this->authorize('viewAny', LockerPackage::class);
        $station = $this->currentStation();
        $package = null;

        if ($request->filled('code')) {
            $package = $this->service->findByCode(trim((string) $request->input('code')));
            if ($package) {
                if (! $this->isAdmin()) {
                    $this->authorize('view', $package);
                }
            }
        } elseif ($request->filled('id')) {
            $package = LockerPackage::with('station')->find($request->input('id'));
            if ($package && ! $this->isAdmin()) {
                $this->authorize('view', $package);
            }
        }

        $prenotatiQuery = LockerPackage::query()
            ->whereIn('status', [LockerPackageStatus::PRENOTATO, LockerPackageStatus::NO_SHOW])
            ->whereDate('expected_pickup_date', '<=', today()->addDay())
            ->orderBy('expected_pickup_date')
            ->limit(50);
        $this->service->scopeStationQuery($prenotatiQuery, $station, $this->isAdmin());

        return view('Backend.LockerPoint.intake', [
            'package' => $package,
            'prenotati' => $prenotatiQuery->get(),
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Accettazione pacco',
        ]);
    }

    public function intake(string $id)
    {
        $package = LockerPackage::with('station')->findOrFail($id);
        $this->authorize('intake', $package);

        $station = $this->currentStation();
        $prenotatiQuery = LockerPackage::query()
            ->whereIn('status', [LockerPackageStatus::PRENOTATO, LockerPackageStatus::NO_SHOW])
            ->orderBy('expected_pickup_date')
            ->limit(50);
        $this->service->scopeStationQuery($prenotatiQuery, $station, $this->isAdmin());

        return view('Backend.LockerPoint.intake', [
            'package' => $package,
            'prenotati' => $prenotatiQuery->get(),
            'station' => $package->station,
            'controller' => self::class,
            'titoloPagina' => 'Accettazione '.$package->code,
        ]);
    }

    public function storeIntake(Request $request, string $id)
    {
        $package = LockerPackage::with('station')->findOrFail($id);
        $this->authorize('intake', $package);

        $request->validate([
            'photo' => ['required', 'image', 'max:'.LockerConfig::maxPhotoKb()],
        ]);

        try {
            $this->service->acceptIntake($package, $request->file('photo'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['photo' => $e->getMessage()]);
        }

        return redirect()
            ->action([self::class, 'show'], $package->id)
            ->with('success', 'Pacco accettato in giacenza. Stampa l\'etichetta barcode.')
            ->with('print_label', true);
    }

    public function settings()
    {
        $this->authorize('manageSettings', LockerPackage::class);

        return view('Backend.LockerPoint.settings', [
            'settings' => \App\Models\LockerSetting::singleton(),
            'controller' => self::class,
            'titoloPagina' => 'Impostazioni Locker Point',
            'stations' => LockerStation::query()->with('user')->orderBy('name')->get(),
        ]);
    }

    public function updateSettings(UpdateLockerSettingsRequest $request)
    {
        $this->authorize('manageSettings', LockerPackage::class);
        $this->service->applySettingsUpdate($request->validated());

        return redirect()->back()->with('success', 'Impostazioni Locker Point aggiornate.');
    }

    public function stationSettings()
    {
        $this->authorize('manageStationSettings', LockerPackage::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        return view('Backend.LockerPoint.station-settings', [
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'La mia postazione locker',
            'plainApiKey' => session('locker_station_api_key'),
        ]);
    }

    public function updateStationSettings(Request $request)
    {
        $this->authorize('manageStationSettings', LockerPackage::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'max_capacity' => ['required', 'integer', 'min:1'],
            'min_days' => ['required', 'integer', 'min:1'],
            'max_packages_per_booking' => ['required', 'integer', 'min:1'],
            'online_intake_enabled' => ['nullable', 'boolean'],
        ]);
        $validated['online_intake_enabled'] = $request->boolean('online_intake_enabled');

        $this->stations->updateStation($station, $validated);

        return redirect()->back()->with('success', 'Impostazioni postazione aggiornate.');
    }

    public function requestStationApi()
    {
        $this->authorize('manageStationSettings', LockerPackage::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        $this->stations->requestApi($station);

        return redirect()->back()->with('success', 'Richiesta API inviata. Un admin dovrà abilitare la chiave.');
    }

    public function stationsIndex()
    {
        $this->authorize('manageStationApis', LockerPackage::class);

        return view('Backend.LockerPoint.stations-admin', [
            'stations' => LockerStation::query()->with('user')->orderByDesc('api_requested_at')->orderBy('name')->get(),
            'controller' => self::class,
            'titoloPagina' => 'Postazioni Locker Point',
            'plainApiKey' => session('locker_station_api_key'),
        ]);
    }

    public function enableStationApi(string $id)
    {
        $this->authorize('manageStationApis', LockerPackage::class);
        $station = LockerStation::findOrFail($id);
        $result = $this->stations->enableApi($station);

        return redirect()->back()
            ->with('success', 'API abilitate per '.$result['station']->name.'. Copia la chiave ora.')
            ->with('locker_station_api_key', $result['plain_key']);
    }

    public function regenerateStationApi(string $id)
    {
        $this->authorize('manageStationApis', LockerPackage::class);
        $station = LockerStation::findOrFail($id);
        $result = $this->stations->regenerateApiKey($station);

        return redirect()->back()
            ->with('success', 'Nuova API key generata per '.$result['station']->name.'.')
            ->with('locker_station_api_key', $result['plain_key']);
    }

    public function disableStationApi(string $id)
    {
        $this->authorize('manageStationApis', LockerPackage::class);
        $station = LockerStation::findOrFail($id);
        $this->stations->disableApi($station);

        return redirect()->back()->with('success', 'API disabilitate per '.$station->name.'.');
    }

    public function create()
    {
        $this->authorize('create', LockerPackage::class);
        $station = $this->currentStation();

        return view('Backend.LockerPoint.create', [
            'settings' => $this->service->settingsFor($station),
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Nuovo '.LockerPackage::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', LockerPackage::class);
        $station = $this->currentStation();
        if (! $this->isAdmin()) {
            abort_unless($station, 403, 'Postazione locker non disponibile.');
        }

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'expected_pickup_date' => ['required', 'date'],
            'recipient_email' => ['nullable', 'email'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $package = $this->service->create($validated, 'desk', $station);
        } catch (LockerNoAvailabilityException $e) {
            return redirect()->back()->withInput()->withErrors(['expected_pickup_date' => $e->getMessage()]);
        }

        return redirect()
            ->action([self::class, 'intake'], $package->id)
            ->with('success', 'Prenotazione creata. Scatta la foto del pacco per completare l\'accettazione.');
    }

    public function show(string $id)
    {
        $package = LockerPackage::with('station')->findOrFail($id);
        $this->authorize('view', $package);

        $pricingPreview = null;
        if ($package->status === LockerPackageStatus::IN_GIACENZA) {
            $pricingPreview = $this->service->computeStoragePrice($package);
        }

        return view('Backend.LockerPoint.show', [
            'package' => $package,
            'pricingPreview' => $pricingPreview,
            'controller' => self::class,
            'titoloPagina' => $package->code,
            'breadcrumbs' => [action([self::class, 'index']) => 'Pacchi locker'],
        ]);
    }

    public function action(LockerPackageActionRequest $request, string $id)
    {
        $package = LockerPackage::with('station')->findOrFail($id);

        if ($request->input('action') === 'delete') {
            $this->authorize('delete', $package);
            $package->delete();

            return redirect()
                ->action([self::class, 'index'], ['view' => request('view', 'oggi')])
                ->with('success', 'Pacco eliminato definitivamente.');
        }

        $this->authorize('update', $package);

        try {
            match ($request->input('action')) {
                'intake' => $this->service->acceptIntake($package, $request->file('photo')),
                'deliver' => $this->service->deliverDesk(
                    $package,
                    $request->input('paymentMethod', 'Contanti'),
                    $request->input('signerName')
                ),
                'cancel' => $this->service->cancel($package),
                'no-show' => $this->service->markNoShow($package),
                default => throw new InvalidArgumentException('Azione non supportata'),
            };
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Operazione completata.');
    }

    public function destroy(string $id)
    {
        $package = LockerPackage::findOrFail($id);
        $this->authorize('delete', $package);
        $package->delete();

        return redirect()
            ->action([self::class, 'index'], ['view' => request('view', 'oggi')])
            ->with('success', 'Pacco eliminato definitivamente.');
    }

    public function pdfLabel(string $id)
    {
        $package = LockerPackage::findOrFail($id);
        $this->authorize('view', $package);

        return LockerTagPdf::make($package)->download('etichetta-'.$package->code.'.pdf');
    }

    public function photo(string $id)
    {
        $package = LockerPackage::findOrFail($id);
        $this->authorize('view', $package);

        $path = (string) $package->photo_path;
        abort_unless($path !== '' && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'intake.jpg', [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

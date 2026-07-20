<?php

namespace App\Http\Controllers\Backend;

use App\Enums\LuggageDepositStatus;
use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LuggageDepositActionRequest;
use App\Http\Requests\UpdateLuggageSettingsRequest;
use App\Http\Services\LuggageDepositService;
use App\Http\Services\LuggageStationService;
use App\Http\Support\LuggageTagPdf;
use App\Models\LuggageDeposit;
use App\Models\LuggageStation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LuggageDepositController extends Controller
{
    public function __construct(
        private LuggageDepositService $service,
        private LuggageStationService $stations,
    ) {
        $this->middleware('can:viewAny,'.LuggageDeposit::class);
    }

    protected function currentStation(): ?LuggageStation
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
        $this->authorize('viewAny', LuggageDeposit::class);
        $station = $this->currentStation();

        $stats = $this->service->stats(null, null, $station, $this->isAdmin());

        return view('Backend.LuggageDeposit.dashboard', [
            'stats' => $stats,
            'station' => $station,
            'titoloPagina' => $station ? ('Deposito Bagagli — '.$station->name) : 'Deposito Bagagli',
            'controller' => self::class,
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', LuggageDeposit::class);
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
                'html' => base64_encode(view('Backend.LuggageDeposit.tabella', [
                    'records' => $records,
                    'controller' => self::class,
                ])->render()),
            ];
        }

        return view('Backend.LuggageDeposit.index', [
            'records' => $records,
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Elenco '.LuggageDeposit::NOME_PLURALE,
            'view' => $request->get('view', 'oggi'),
            'testoNuovo' => 'Nuovo '.LuggageDeposit::NOME_SINGOLARE,
            'testoCerca' => 'Cerca codice o cliente',
        ]);
    }

    public function pipeline()
    {
        $station = $this->currentStation();
        $base = LuggageDeposit::query();
        $this->service->scopeStationQuery($base, $station, $this->isAdmin());

        $columns = [
            'prenotati' => (clone $base)->whereIn('status', [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::NO_SHOW])->orderBy('booking_date')->get(),
            'attivi' => (clone $base)->where('status', LuggageDepositStatus::CHECK_IN)->orderBy('checked_in_at')->get(),
            'completati' => (clone $base)->where('status', LuggageDepositStatus::COMPLETATO)->latest('checked_out_at')->limit(20)->get(),
        ];

        return view('Backend.LuggageDeposit.pipeline', [
            'columns' => $columns,
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Pipeline Deposito Bagagli',
        ]);
    }

    public function checkInPage(Request $request)
    {
        $station = $this->currentStation();
        $deposit = null;
        if ($request->filled('code')) {
            $deposit = $this->service->findByCode($request->input('code'));
            if ($deposit && ! $this->isAdmin()) {
                $this->authorize('view', $deposit);
            }
        }

        $prenotatiQuery = LuggageDeposit::query()
            ->whereIn('status', [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::NO_SHOW])
            ->whereDate('booking_date', '<=', today()->addDay())
            ->orderBy('booking_date')
            ->limit(50);
        $this->service->scopeStationQuery($prenotatiQuery, $station, $this->isAdmin());

        return view('Backend.LuggageDeposit.check-in', [
            'deposit' => $deposit,
            'prenotati' => $prenotatiQuery->get(),
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Check-in Bagagli',
        ]);
    }

    public function checkOutPage(Request $request)
    {
        $station = $this->currentStation();
        $deposit = null;
        $preview = null;

        if ($request->filled('code')) {
            $deposit = $this->service->findByCode($request->input('code'));
            if ($deposit) {
                if (! $this->isAdmin()) {
                    $this->authorize('view', $deposit);
                }
                if ($deposit->status === LuggageDepositStatus::CHECK_IN) {
                    $preview = $this->service->computeStoragePrice($deposit);
                }
            }
        }

        $attiviQuery = LuggageDeposit::query()
            ->where('status', LuggageDepositStatus::CHECK_IN)
            ->orderBy('checked_in_at')
            ->limit(50);
        $this->service->scopeStationQuery($attiviQuery, $station, $this->isAdmin());

        return view('Backend.LuggageDeposit.check-out', [
            'deposit' => $deposit,
            'preview' => $preview,
            'attivi' => $attiviQuery->get(),
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Check-out Bagagli',
        ]);
    }

    public function settings()
    {
        $this->authorize('manageSettings', LuggageDeposit::class);

        return view('Backend.LuggageDeposit.settings', [
            'settings' => \App\Models\LuggageSetting::singleton(),
            'controller' => self::class,
            'titoloPagina' => 'Impostazioni Deposito Bagagli',
            'stations' => LuggageStation::query()->with('user')->orderBy('name')->get(),
        ]);
    }

    public function updateSettings(UpdateLuggageSettingsRequest $request)
    {
        $this->authorize('manageSettings', LuggageDeposit::class);

        $validated = $request->validated();

        $this->service->applySettingsUpdate($validated);

        return redirect()->back()->with('success', 'Impostazioni deposito bagagli aggiornate.');
    }

    public function stationSettings()
    {
        $this->authorize('manageStationSettings', LuggageDeposit::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        return view('Backend.LuggageDeposit.station-settings', [
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'La mia postazione deposito',
            'plainApiKey' => session('luggage_station_api_key'),
        ]);
    }

    public function updateStationSettings(Request $request)
    {
        $this->authorize('manageStationSettings', LuggageDeposit::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'max_capacity' => ['required', 'integer', 'min:1'],
            'min_days' => ['required', 'integer', 'min:1'],
            'max_bags_per_booking' => ['required', 'integer', 'min:1'],
            'online_booking_enabled' => ['nullable', 'boolean'],
        ]);
        $validated['online_booking_enabled'] = $request->boolean('online_booking_enabled');

        $this->stations->updateStation($station, $validated);

        return redirect()->back()->with('success', 'Impostazioni postazione aggiornate.');
    }

    public function requestStationApi()
    {
        $this->authorize('manageStationSettings', LuggageDeposit::class);
        $station = $this->currentStation();
        abort_unless($station, 404);

        $this->stations->requestApi($station);

        return redirect()->back()->with('success', 'Richiesta API inviata. Un admin dovrà abilitare la chiave.');
    }

    public function stationsIndex()
    {
        $this->authorize('manageStationApis', LuggageDeposit::class);

        return view('Backend.LuggageDeposit.stations-admin', [
            'stations' => LuggageStation::query()->with('user')->orderByDesc('api_requested_at')->orderBy('name')->get(),
            'controller' => self::class,
            'titoloPagina' => 'Postazioni deposito bagagli',
            'plainApiKey' => session('luggage_station_api_key'),
        ]);
    }

    public function enableStationApi(string $id)
    {
        $this->authorize('manageStationApis', LuggageDeposit::class);
        $station = LuggageStation::findOrFail($id);
        $result = $this->stations->enableApi($station);

        return redirect()->back()
            ->with('success', 'API abilitate per '.$result['station']->name.'. Copia la chiave ora: non sarà più mostrata per intero.')
            ->with('luggage_station_api_key', $result['plain_key']);
    }

    public function regenerateStationApi(string $id)
    {
        $this->authorize('manageStationApis', LuggageDeposit::class);
        $station = LuggageStation::findOrFail($id);
        $result = $this->stations->regenerateApiKey($station);

        return redirect()->back()
            ->with('success', 'Nuova API key generata per '.$result['station']->name.'.')
            ->with('luggage_station_api_key', $result['plain_key']);
    }

    public function disableStationApi(string $id)
    {
        $this->authorize('manageStationApis', LuggageDeposit::class);
        $station = LuggageStation::findOrFail($id);
        $this->stations->disableApi($station);

        return redirect()->back()->with('success', 'API disabilitate per '.$station->name.'.');
    }

    public function report(Request $request)
    {
        $this->authorize('viewReports', LuggageDeposit::class);

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : today()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : today();
        $stats = $this->service->stats($from, $to, null, true);

        return view('Backend.LuggageDeposit.report', [
            'stats' => $stats,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'controller' => self::class,
            'titoloPagina' => 'Report Deposito Bagagli',
        ]);
    }

    public function create()
    {
        $this->authorize('create', LuggageDeposit::class);
        $station = $this->currentStation();

        return view('Backend.LuggageDeposit.create', [
            'settings' => $this->service->settingsFor($station),
            'station' => $station,
            'controller' => self::class,
            'titoloPagina' => 'Nuovo '.LuggageDeposit::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', LuggageDeposit::class);
        $station = $this->currentStation();
        if (! $this->isAdmin()) {
            abort_unless($station, 403, 'Postazione deposito non disponibile.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'booking_date' => ['required', 'date'],
            'bag_count' => ['nullable', 'integer', 'min:1'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'expected_check_in' => ['nullable', 'date', 'after_or_equal:booking_date'],
            'expected_check_out' => ['required', 'date', 'after_or_equal:booking_date'],
        ]);

        if (
            ! empty($validated['expected_check_in'])
            && Carbon::parse($validated['expected_check_out'])->lt(Carbon::parse($validated['expected_check_in']))
        ) {
            return redirect()->back()->withInput()->withErrors([
                'expected_check_out' => 'La data di ritiro deve essere uguale o successiva al check-in previsto.',
            ]);
        }

        try {
            $deposit = $this->service->create($validated, 'SPORTELLO', $station);
        } catch (LuggageNoAvailabilityException $e) {
            return redirect()->back()->withInput()->withErrors(['bag_count' => $e->getMessage()]);
        }

        return redirect()
            ->action([self::class, 'show'], $deposit->id)
            ->with('success', 'Deposito creato. Stampa i tag bagaglio e fai firmare il documento al cliente.')
            ->with('print_documents', true);
    }

    public function show(string $id)
    {
        $deposit = LuggageDeposit::with('station')->findOrFail($id);
        $this->authorize('view', $deposit);

        $pricingPreview = null;
        if ($deposit->status === LuggageDepositStatus::CHECK_IN) {
            $pricingPreview = $this->service->computeStoragePrice($deposit);
        }

        return view('Backend.LuggageDeposit.show', [
            'deposit' => $deposit,
            'pricingPreview' => $pricingPreview,
            'controller' => self::class,
            'titoloPagina' => $deposit->code,
            'breadcrumbs' => [action([self::class, 'index']) => 'Depositi bagagli'],
        ]);
    }

    public function action(LuggageDepositActionRequest $request, string $id)
    {
        $deposit = LuggageDeposit::with('station')->findOrFail($id);

        if ($request->input('action') === 'delete') {
            $this->authorize('delete', $deposit);
            $deposit->delete();

            return redirect()
                ->action([self::class, 'index'], ['view' => request('view', 'oggi')])
                ->with('success', 'Deposito eliminato definitivamente dal database.');
        }

        $this->authorize('update', $deposit);

        try {
            match ($request->input('action')) {
                'check-in' => $this->service->checkIn($deposit, $request->input('bagTags')),
                'check-out' => $this->service->checkOut($deposit, $request->input('paymentMethod', 'Contanti')),
                'cancel' => $this->service->cancel($deposit),
                'no-show' => $this->service->markNoShow($deposit),
                default => throw new InvalidArgumentException('Azione non supportata'),
            };
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Operazione completata.');
    }

    public function destroy(string $id)
    {
        $deposit = LuggageDeposit::findOrFail($id);
        $this->authorize('delete', $deposit);

        $deposit->delete();

        return redirect()
            ->action([self::class, 'index'], ['view' => request('view', 'oggi')])
            ->with('success', 'Deposito eliminato definitivamente dal database.');
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewReports', LuggageDeposit::class);

        return app(\App\Http\Controllers\Api\Admin\LuggageExportController::class)->csv($request);
    }

    public function pdfReceipt(string $id)
    {
        $deposit = LuggageDeposit::findOrFail($id);
        $this->authorize('view', $deposit);

        $pdf = Pdf::loadView('Backend.LuggageDeposit.pdf.receipt', ['deposit' => $deposit]);

        return $pdf->download('ricevuta-'.$deposit->code.'.pdf');
    }

    public function pdfTags(string $id)
    {
        $deposit = LuggageDeposit::findOrFail($id);
        $this->authorize('view', $deposit);

        $pdf = LuggageTagPdf::make($deposit, $this->service->resolveBagTags($deposit));

        return $pdf->download('tag-'.$deposit->code.'.pdf');
    }

    public function pdfAgreement(string $id)
    {
        $deposit = LuggageDeposit::findOrFail($id);
        $this->authorize('view', $deposit);

        $pdf = Pdf::loadView('Backend.LuggageDeposit.pdf.agreement', [
            'deposit' => $deposit,
            'tags' => $this->service->resolveBagTags($deposit),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="documento-'.$deposit->code.'.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}

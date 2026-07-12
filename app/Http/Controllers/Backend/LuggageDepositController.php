<?php

namespace App\Http\Controllers\Backend;

use App\Enums\LuggageDepositStatus;
use App\Exceptions\LuggageNoAvailabilityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LuggageDepositActionRequest;
use App\Http\Requests\UpdateLuggageSettingsRequest;
use App\Http\Services\LuggageDepositService;
use App\Models\LuggageDeposit;
use App\Http\Support\LuggageTagPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class LuggageDepositController extends Controller
{
    public function __construct(private LuggageDepositService $service)
    {
        $this->middleware('can:viewAny,'.LuggageDeposit::class);
    }

    public function dashboard()
    {
        $this->authorize('viewAny', LuggageDeposit::class);

        $stats = $this->service->stats();

        return view('Backend.LuggageDeposit.dashboard', [
            'stats' => $stats,
            'titoloPagina' => 'Deposito Bagagli',
            'controller' => self::class,
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', LuggageDeposit::class);

        $records = $this->service->list(
            array_merge($request->only(['view', 'q', 'status', 'source']), ['view' => $request->get('view', 'oggi')]),
            (int) $request->get('page', 1),
            (int) config('configurazione.paginazione', 25)
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
            'controller' => self::class,
            'titoloPagina' => 'Elenco '.LuggageDeposit::NOME_PLURALE,
            'view' => $request->get('view', 'oggi'),
            'testoNuovo' => 'Nuovo '.LuggageDeposit::NOME_SINGOLARE,
            'testoCerca' => 'Cerca codice o cliente',
        ]);
    }

    public function pipeline()
    {
        $columns = [
            'prenotati' => LuggageDeposit::whereIn('status', [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::NO_SHOW])->orderBy('booking_date')->get(),
            'attivi' => LuggageDeposit::where('status', LuggageDepositStatus::CHECK_IN)->orderBy('checked_in_at')->get(),
            'completati' => LuggageDeposit::where('status', LuggageDepositStatus::COMPLETATO)->latest('checked_out_at')->limit(20)->get(),
        ];

        return view('Backend.LuggageDeposit.pipeline', [
            'columns' => $columns,
            'controller' => self::class,
            'titoloPagina' => 'Pipeline Deposito Bagagli',
        ]);
    }

    public function checkInPage(Request $request)
    {
        $deposit = null;
        if ($request->filled('code')) {
            $deposit = $this->service->findByCode($request->input('code'));
        }

        $prenotati = LuggageDeposit::whereIn('status', [LuggageDepositStatus::PRENOTATO, LuggageDepositStatus::NO_SHOW])
            ->whereDate('booking_date', '<=', today()->addDay())
            ->orderBy('booking_date')
            ->limit(50)
            ->get();

        return view('Backend.LuggageDeposit.check-in', [
            'deposit' => $deposit,
            'prenotati' => $prenotati,
            'controller' => self::class,
            'titoloPagina' => 'Check-in Bagagli',
        ]);
    }

    public function checkOutPage(Request $request)
    {
        $deposit = null;
        $preview = null;

        if ($request->filled('code')) {
            $deposit = $this->service->findByCode($request->input('code'));
            if ($deposit && $deposit->status === LuggageDepositStatus::CHECK_IN) {
                $preview = $this->service->computeStoragePrice($deposit);
            }
        }

        $attivi = LuggageDeposit::where('status', LuggageDepositStatus::CHECK_IN)
            ->orderBy('checked_in_at')
            ->limit(50)
            ->get();

        return view('Backend.LuggageDeposit.check-out', [
            'deposit' => $deposit,
            'preview' => $preview,
            'attivi' => $attivi,
            'controller' => self::class,
            'titoloPagina' => 'Check-out Bagagli',
        ]);
    }

    public function settings()
    {
        $this->authorize('manageSettings', LuggageDeposit::class);

        return view('Backend.LuggageDeposit.settings', [
            'settings' => $this->service->getSettings(),
            'controller' => self::class,
            'titoloPagina' => 'Impostazioni Deposito Bagagli',
        ]);
    }

    public function updateSettings(UpdateLuggageSettingsRequest $request)
    {
        $this->authorize('manageSettings', LuggageDeposit::class);

        $validated = $request->validated();

        $this->service->applySettingsUpdate($validated);

        return redirect()->back()->with('success', 'Impostazioni deposito bagagli aggiornate.');
    }

    public function report(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : today()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : today();
        $stats = $this->service->stats($from, $to);

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

        return view('Backend.LuggageDeposit.create', [
            'settings' => $this->service->getSettings(),
            'controller' => self::class,
            'titoloPagina' => 'Nuovo '.LuggageDeposit::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', LuggageDeposit::class);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'booking_date' => ['required', 'date'],
            'bag_count' => ['nullable', 'integer', 'min:1'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'cliente_id' => ['nullable', 'integer', 'exists:clienti,id'],
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
            $deposit = $this->service->create($validated, 'SPORTELLO');
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
        $deposit = LuggageDeposit::findOrFail($id);
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
        $deposit = LuggageDeposit::findOrFail($id);

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
        $this->authorize('viewAny', LuggageDeposit::class);

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

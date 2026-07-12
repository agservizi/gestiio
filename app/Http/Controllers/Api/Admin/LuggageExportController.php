<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use App\Http\Support\LuggageTagPdf;
use App\Models\LuggageDeposit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LuggageExportController extends Controller
{
    public function __construct(private LuggageDepositService $service)
    {
    }

    public function csv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', LuggageDeposit::class);

        $paginator = $this->service->list(
            $request->only(['view', 'q', 'status', 'from', 'to']),
            1,
            10000
        );

        $filename = 'depositi-bagagli-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($paginator) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Codice', 'Cliente', 'Email', 'Telefono', 'Borse', 'Stato',
                'Data prenotazione', 'Check-in', 'Check-out', 'Importo', 'Fonte',
            ], ';');

            foreach ($paginator->items() as $deposit) {
                fputcsv($handle, [
                    $deposit->code,
                    $deposit->customer_name,
                    $deposit->customer_email,
                    $deposit->customer_phone,
                    $deposit->bag_count,
                    $deposit->status->value,
                    $deposit->booking_date?->format('Y-m-d'),
                    $deposit->checked_in_at?->format('Y-m-d H:i'),
                    $deposit->checked_out_at?->format('Y-m-d H:i'),
                    $deposit->total_amount,
                    $deposit->source,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function receipt(LuggageDeposit $deposit)
    {
        $this->authorize('view', $deposit);

        $pdf = Pdf::loadView('Backend.LuggageDeposit.pdf.receipt', [
            'deposit' => $deposit,
        ]);

        return $pdf->download('ricevuta-'.$deposit->code.'.pdf');
    }

    public function tags(LuggageDeposit $deposit)
    {
        $this->authorize('view', $deposit);

        $pdf = LuggageTagPdf::make($deposit, $this->service->resolveBagTags($deposit));

        return $pdf->download('tag-'.$deposit->code.'.pdf');
    }

    public function agreement(LuggageDeposit $deposit)
    {
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

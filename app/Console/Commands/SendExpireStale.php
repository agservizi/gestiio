<?php

namespace App\Console\Commands;

use App\Enums\SendRequestStatus;
use App\Http\Services\SendRequestStatusService;
use App\Models\SendRequest;
use Illuminate\Console\Command;

class SendExpireStale extends Command
{
    protected $signature = 'send:expire-stale';

    protected $description = 'Imposta expired sulle pratiche SEND oltre SLA di completamento';

    public function handle(SendRequestStatusService $statusService): int
    {
        $hours = (int) config('send.sla.completion_hours', 72);
        if ($hours <= 0) {
            $this->info('SLA completion disabilitata.');

            return self::SUCCESS;
        }

        $open = [
            SendRequestStatus::SUBMITTED->value,
            SendRequestStatus::AWAITING_ASSIGNMENT->value,
            SendRequestStatus::ASSIGNED->value,
            SendRequestStatus::TAKEN_IN_CHARGE->value,
            SendRequestStatus::PROCESSING->value,
            SendRequestStatus::INTEGRATION_REQUIRED->value,
            SendRequestStatus::RESUBMITTED->value,
        ];

        $cutoff = now()->subHours($hours);
        $expired = 0;

        SendRequest::query()
            ->whereIn('status', $open)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($w) use ($cutoff) {
                    $w->whereNotNull('submitted_at')->where('submitted_at', '<=', $cutoff);
                })->orWhere(function ($w) use ($cutoff) {
                    $w->whereNull('submitted_at')->where('created_at', '<=', $cutoff);
                });
            })
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($statusService, &$expired) {
                foreach ($rows as $request) {
                    if ($request->status->isTerminal()) {
                        continue;
                    }
                    try {
                        $statusService->transition($request, SendRequestStatus::EXPIRED, null, 'Scadenza automatica SLA completamento');
                        $expired++;
                    } catch (\Throwable $e) {
                        $this->warn($request->request_number.': '.$e->getMessage());
                    }
                }
            });

        $this->info("Pratiche scadute: {$expired}");

        return self::SUCCESS;
    }
}

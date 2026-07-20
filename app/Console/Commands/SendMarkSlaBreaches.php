<?php

namespace App\Console\Commands;

use App\Enums\SendRequestStatus;
use App\Http\Services\SendRequestService;
use App\Models\SendRequest;
use App\Notifications\NotificaSendAssigned;
use Illuminate\Console\Command;

class SendMarkSlaBreaches extends Command
{
    protected $signature = 'send:mark-sla-breaches';

    protected $description = 'Segnala pratiche SEND oltre SLA (presa in carico / lavorazione)';

    public function handle(SendRequestService $service): int
    {
        $takeChargeHours = (int) config('send.sla.take_charge_hours', 8);
        $processingHours = (int) config('send.sla.processing_hours', 24);
        $notified = 0;

        // Assegnate non prese in carico oltre SLA
        $staleAssigned = SendRequest::query()
            ->where('status', SendRequestStatus::ASSIGNED->value)
            ->whereNotNull('assigned_at')
            ->where('assigned_at', '<=', now()->subHours($takeChargeHours))
            ->whereDoesntHave('notes', function ($q) {
                $q->where('note_type', 'sla_breach');
            })
            ->with('supervisor')
            ->get();

        foreach ($staleAssigned as $request) {
            $service->addNote(
                $request,
                $request->supervisor ?: $request->creator,
                'SLA presa in carico superata ('.$takeChargeHours.'h).',
                'internal',
                'sla_breach'
            );
            try {
                $request->supervisor?->notify(new NotificaSendAssigned($request));
            } catch (\Throwable $e) {
            }
            $notified++;
        }

        // In lavorazione oltre SLA processing
        $staleProcessing = SendRequest::query()
            ->whereIn('status', [
                SendRequestStatus::TAKEN_IN_CHARGE->value,
                SendRequestStatus::PROCESSING->value,
            ])
            ->where(function ($q) use ($processingHours) {
                $q->where(function ($w) use ($processingHours) {
                    $w->whereNotNull('processing_started_at')
                        ->where('processing_started_at', '<=', now()->subHours($processingHours));
                })->orWhere(function ($w) use ($processingHours) {
                    $w->whereNull('processing_started_at')
                        ->whereNotNull('taken_in_charge_at')
                        ->where('taken_in_charge_at', '<=', now()->subHours($processingHours));
                });
            })
            ->whereDoesntHave('notes', function ($q) {
                $q->where('note_type', 'sla_breach_processing');
            })
            ->with('supervisor')
            ->get();

        foreach ($staleProcessing as $request) {
            $service->addNote(
                $request,
                $request->supervisor ?: $request->creator,
                'SLA lavorazione superata ('.$processingHours.'h).',
                'internal',
                'sla_breach_processing'
            );
            try {
                $request->supervisor?->notify(new NotificaSendAssigned($request));
            } catch (\Throwable $e) {
            }
            $notified++;
        }

        $this->info("SLA breaches segnalati: {$notified}");

        return self::SUCCESS;
    }
}

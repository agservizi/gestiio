<?php

namespace App\Http\Services;

use App\Enums\SendRequestStatus;
use App\Models\SendRequest;
use App\Models\SendRequestStatusHistory;
use App\Models\User;
use InvalidArgumentException;

class SendRequestStatusService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['assigned', 'awaiting_assignment', 'cancelled', 'expired'],
        'awaiting_assignment' => ['assigned', 'cancelled', 'expired'],
        'assigned' => ['taken_in_charge', 'cancelled', 'assigned', 'expired'],
        'taken_in_charge' => ['processing', 'cancelled', 'assigned', 'expired'],
        'processing' => ['integration_required', 'completed', 'rejected', 'cancelled', 'expired'],
        'integration_required' => ['resubmitted', 'cancelled', 'expired'],
        'resubmitted' => ['processing', 'taken_in_charge', 'cancelled', 'expired'],
        'completed' => ['delivered', 'cancelled'],
        'delivered' => ['closed'],
        'closed' => [],
        'rejected' => ['draft'],
        'cancelled' => ['draft'],
        'expired' => ['draft'],
    ];

    public function __construct(private SendAuditService $audit)
    {
    }

    public function canTransition(SendRequestStatus $from, SendRequestStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public function transition(
        SendRequest $request,
        SendRequestStatus $to,
        ?User $actor = null,
        ?string $reason = null,
        array $metadata = []
    ): SendRequest {
        $from = $request->status;
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException("Transizione non consentita: {$from->value} → {$to->value}");
        }

        $before = ['status' => $from->value];
        $request->status = $to;
        $this->stampTimestamps($request, $to);
        if ($reason !== null) {
            if ($to === SendRequestStatus::REJECTED) {
                $request->rejection_reason = $reason;
            } elseif ($to === SendRequestStatus::CANCELLED) {
                $request->cancellation_reason = $reason;
            } elseif ($to === SendRequestStatus::INTEGRATION_REQUIRED) {
                $request->integration_reason = $reason;
            }
        }
        $request->updated_by = $actor?->id;
        $request->version = (int) $request->version + 1;
        $request->save();

        SendRequestStatusHistory::query()->create([
            'send_request_id' => $request->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'changed_by' => $actor?->id,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);

        $this->audit->log('status_change', $request, $before, ['status' => $to->value], $reason, $metadata);

        return $request->fresh();
    }

    private function stampTimestamps(SendRequest $request, SendRequestStatus $to): void
    {
        match ($to) {
            SendRequestStatus::SUBMITTED => $request->submitted_at = $request->submitted_at ?: now(),
            SendRequestStatus::ASSIGNED => $request->assigned_at = now(),
            SendRequestStatus::TAKEN_IN_CHARGE => $request->taken_in_charge_at = now(),
            SendRequestStatus::PROCESSING => $request->processing_started_at = $request->processing_started_at ?: now(),
            SendRequestStatus::COMPLETED => $request->completed_at = now(),
            SendRequestStatus::DELIVERED => $request->delivered_at = now(),
            SendRequestStatus::CLOSED => $request->closed_at = now(),
            SendRequestStatus::REJECTED => $request->rejected_at = now(),
            SendRequestStatus::CANCELLED => $request->cancelled_at = now(),
            default => null,
        };
    }
}

<?php

namespace App\Http\Services;

use App\Enums\SendRequestStatus;
use App\Models\Notifica;
use App\Models\SendRequest;
use App\Models\SendRequestAssignment;
use App\Models\SendSetting;
use App\Models\User;
use App\Notifications\NotificaSendAwaitingAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class SendAssignmentService
{
    public function __construct(
        private SendRequestStatusService $statusService,
        private SendAuditService $audit,
        private SendNotificationService $notifications,
    ) {
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    public function eligibleSupervisors()
    {
        return User::query()
            ->permission(['send.requests.process'])
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get()
            ->filter(fn (User $u) => $u->can('servizio_send') || $u->can('admin'))
            ->filter(fn (User $u) => $u->can('send.requests.process'))
            ->values();
    }

    public function assign(
        SendRequest $request,
        ?User $supervisor,
        User $actor,
        string $method = 'manual',
        ?string $reason = null
    ): SendRequest {
        return DB::transaction(function () use ($request, $supervisor, $actor, $method, $reason) {
            $locked = SendRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $supervisor) {
                if (! in_array($locked->status, [SendRequestStatus::SUBMITTED, SendRequestStatus::AWAITING_ASSIGNMENT], true)) {
                    throw new InvalidArgumentException('Stato non valido per attesa assegnazione.');
                }
                $this->statusService->transition($locked, SendRequestStatus::AWAITING_ASSIGNMENT, $actor, $reason);
                $this->notifyAdminsNoSupervisor($locked);

                return $locked->fresh();
            }

            if (! $this->isEligible($supervisor)) {
                throw new InvalidArgumentException('Supervisore non abilitato SEND.');
            }

            if ($locked->assigned_supervisor_id && $locked->assigned_supervisor_id !== $supervisor->id) {
                SendRequestAssignment::query()
                    ->where('send_request_id', $locked->id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => now()]);
            }

            $locked->assigned_supervisor_id = $supervisor->id;
            $locked->save();

            SendRequestAssignment::query()->create([
                'send_request_id' => $locked->id,
                'supervisor_id' => $supervisor->id,
                'assigned_by' => $actor->id,
                'assignment_method' => $method,
                'assigned_at' => now(),
                'reason' => $reason,
            ]);

            if ($locked->status !== SendRequestStatus::ASSIGNED) {
                $this->statusService->transition($locked, SendRequestStatus::ASSIGNED, $actor, $reason, [
                    'method' => $method,
                    'supervisor_id' => $supervisor->id,
                ]);
            }

            $this->audit->log('assign', $locked, null, [
                'supervisor_id' => $supervisor->id,
                'method' => $method,
            ], $reason);

            $this->notifications->notifySupervisorAssigned($locked, $supervisor);

            return $locked->fresh();
        });
    }

    public function assignAuto(SendRequest $request, User $actor): SendRequest
    {
        $method = SendSetting::getValue('assignment_method', config('send.assignment_method', 'least_open'));

        return DB::transaction(function () use ($request, $actor, $method) {
            if ($method === 'manual') {
                return $this->assign($request, null, $actor, 'manual');
            }

            return $this->assignWithMethod($request, $actor, $method);
        });
    }

    /** Assegnazione automatica all'invio operatore (ignora modalità "solo manuale"). */
    public function assignOnSubmit(SendRequest $request, User $actor, ?int $supervisorId = null): SendRequest
    {
        return DB::transaction(function () use ($request, $actor, $supervisorId) {
            if ($supervisorId) {
                $supervisor = User::findOrFail($supervisorId);

                return $this->assign($request, $supervisor, $actor, 'manual');
            }

            $supervisors = $this->eligibleSupervisors();
            if ($supervisors->isEmpty()) {
                return $this->assign($request, null, $actor, 'auto_none');
            }

            $method = SendSetting::getValue('assignment_method', config('send.assignment_method', 'least_open'));
            if ($method === 'manual') {
                $method = 'least_open';
            }

            return $this->assignWithMethod($request, $actor, $method);
        });
    }

    /** Supervisore prende una pratica non ancora assegnata. */
    public function claim(SendRequest $request, User $actor): SendRequest
    {
        if (! $this->isEligible($actor)) {
            throw new InvalidArgumentException('Supervisore non abilitato SEND.');
        }

        if (! in_array($request->status, [SendRequestStatus::SUBMITTED, SendRequestStatus::AWAITING_ASSIGNMENT], true)) {
            throw new InvalidArgumentException('Stato non valido per presa pratica.');
        }

        if ($request->assigned_supervisor_id && $request->assigned_supervisor_id !== $actor->id) {
            throw new InvalidArgumentException('Pratica già assegnata ad un altro supervisore.');
        }

        return $this->assign($request, $actor, $actor, 'claim');
    }

    /** Assegna pratiche inviate ma senza supervisore (backfill / manutenzione). */
    public function assignPending(?User $actor = null): int
    {
        $actor ??= User::permission('admin')->first() ?? User::permission('send.requests.process')->first();
        if (! $actor) {
            return 0;
        }

        $pending = SendRequest::query()
            ->whereNull('assigned_supervisor_id')
            ->whereIn('status', [SendRequestStatus::SUBMITTED, SendRequestStatus::AWAITING_ASSIGNMENT])
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($pending as $request) {
            $fresh = $request->fresh();
            if (! $fresh || $fresh->assigned_supervisor_id) {
                continue;
            }
            $this->assignOnSubmit($fresh, $actor);
            $count++;
        }

        return $count;
    }

    private function assignWithMethod(SendRequest $request, User $actor, string $method): SendRequest
    {
        $supervisors = $this->eligibleSupervisors();
        if ($supervisors->isEmpty()) {
            return $this->assign($request, null, $actor, 'auto_none');
        }

        $picked = match ($method) {
            'round_robin' => $this->pickRoundRobin($supervisors),
            'default_supervisor' => $this->pickDefault($supervisors),
            default => $this->pickLeastOpen($supervisors),
        };

        return $this->assign(
            $request,
            $picked,
            $actor,
            $method === 'default_supervisor' ? 'default_supervisor' : $method
        );
    }

    public function reassign(SendRequest $request, User $supervisor, User $actor, ?string $reason = null): SendRequest
    {
        if (! in_array($request->status, [
            SendRequestStatus::AWAITING_ASSIGNMENT,
            SendRequestStatus::ASSIGNED,
            SendRequestStatus::TAKEN_IN_CHARGE,
        ], true)) {
            throw new InvalidArgumentException('Stato non valido per riassegnazione.');
        }

        return $this->assign($request, $supervisor, $actor, 'reassign', $reason);
    }

    public function isEligible(User $user): bool
    {
        if (! $user->can('send.requests.process')) {
            return false;
        }

        return $user->can('admin') || $user->can('servizio_send');
    }

    private function pickLeastOpen($supervisors): User
    {
        $openStatuses = [
            SendRequestStatus::ASSIGNED->value,
            SendRequestStatus::TAKEN_IN_CHARGE->value,
            SendRequestStatus::PROCESSING->value,
            SendRequestStatus::RESUBMITTED->value,
        ];

        $counts = SendRequest::query()
            ->selectRaw('assigned_supervisor_id, COUNT(*) as c')
            ->whereIn('status', $openStatuses)
            ->whereIn('assigned_supervisor_id', $supervisors->pluck('id'))
            ->groupBy('assigned_supervisor_id')
            ->pluck('c', 'assigned_supervisor_id');

        return $supervisors->sortBy(fn (User $u) => (int) ($counts[$u->id] ?? 0))->first();
    }

    private function pickRoundRobin($supervisors): User
    {
        $lastId = (int) SendSetting::getValue('rr_last_supervisor_id', '0');
        $sorted = $supervisors->sortBy('id')->values();
        $next = $sorted->first(fn (User $u) => $u->id > $lastId) ?: $sorted->first();
        SendSetting::setValue('rr_last_supervisor_id', (string) $next->id);

        return $next;
    }

    private function pickDefault($supervisors): User
    {
        $defaultId = (int) SendSetting::getValue('default_supervisor_id', '0');
        $found = $supervisors->firstWhere('id', $defaultId);

        return $found ?: $this->pickLeastOpen($supervisors);
    }

    private function notifyAdminsNoSupervisor(SendRequest $request): void
    {
        try {
            Notifica::notificaAdAdmin(
                'SEND: richiesta senza supervisore',
                'La pratica '.$request->request_number.' è in attesa di assegnazione (uuid: '.$request->uuid.').',
                'warning'
            );
        } catch (\Throwable $e) {
            //
        }

        $admins = User::permission('admin')->get();
        foreach ($admins as $admin) {
            try {
                Notification::send($admin, new NotificaSendAwaitingAssignment($request));
            } catch (\Throwable $e) {
                //
            }
        }
    }
}

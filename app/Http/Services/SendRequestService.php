<?php

namespace App\Http\Services;

use App\Contracts\SendProviderInterface;
use App\Enums\SendApplicantType;
use App\Enums\SendPriority;
use App\Enums\SendRequestStatus;
use App\Enums\TipiPortafoglioEnum;
use App\Models\Agente;
use App\Models\MovimentoPortafoglio;
use App\Models\SendRequest;
use App\Models\SendRequestConsent;
use App\Models\SendRequestDelivery;
use App\Models\SendRequestNote;
use App\Models\SendSetting;
use App\Models\User;
use App\Notifications\NotificaSendCancelled;
use App\Notifications\NotificaSendCompleted;
use App\Notifications\NotificaSendDelivered;
use App\Notifications\NotificaSendIntegrationRequired;
use App\Notifications\NotificaSendRejected;
use App\Notifications\NotificaSendTakenInCharge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SendRequestService
{
    public function __construct(
        private SendChecklistService $checklist,
        private SendRequestStatusService $status,
        private SendAssignmentService $assignment,
        private SendDocumentService $documents,
        private SendAuditService $audit,
    ) {
    }

    public function generateRequestNumber(): string
    {
        $year = (int) now()->format('Y');
        $prefix = config('send.number_prefix', 'SEND');

        return DB::transaction(function () use ($year, $prefix) {
            $row = DB::table('send_number_counters')->where('year', $year)->lockForUpdate()->first();
            if (! $row) {
                DB::table('send_number_counters')->insert([
                    'year' => $year,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = DB::table('send_number_counters')->where('year', $year)->lockForUpdate()->first();
            }

            $next = ((int) $row->last_number) + 1;
            DB::table('send_number_counters')->where('year', $year)->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

            return sprintf('%s-%d-%06d', $prefix, $year, $next);
        });
    }

    public function createDraft(User $actor, array $data): SendRequest
    {
        $prezzoCliente = $this->prezzoCliente();
        $prezzoAgente = $this->prezzoAgente();
        $importoFornitore = $this->importoFornitore();

        return DB::transaction(function () use ($actor, $data, $prezzoCliente, $prezzoAgente, $importoFornitore) {
            $this->assertSufficientPlafond($actor, $prezzoAgente);

            $request = SendRequest::query()->create([
                'request_number' => $this->generateRequestNumber(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'applicant_type' => $data['applicant_type'] ?? SendApplicantType::DESTINATARIO->value,
                'status' => SendRequestStatus::DRAFT->value,
                'priority' => $data['priority'] ?? SendSetting::getValue('default_priority', config('send.default_priority', 'normale')),
                'prezzo_cliente' => $prezzoCliente,
                'prezzo_agente' => $prezzoAgente,
                'importo_fornitore' => $importoFornitore,
                'send_notice_identifier' => $data['send_notice_identifier'] ?? null,
                'iun' => $data['iun'] ?? null,
                'sender_entity' => $data['sender_entity'] ?? null,
                'notice_date' => $data['notice_date'] ?? null,
                'received_date' => $data['received_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'notice_pages' => $data['notice_pages'] ?? null,
                'communication_type' => $data['communication_type'] ?? null,
                'initial_notes' => $data['initial_notes'] ?? null,
            ]);

            $this->syncSubjects($request, $data['subjects'] ?? []);
            $this->checklist->syncForRequest($request);
            if (! empty($data['checklist'])) {
                $this->checklist->updateFromInput($request, $data['checklist']);
            }
            $this->syncConsents($request, $actor, $data['consents'] ?? []);

            $movimento = $this->chargePlafond($request, $actor, $prezzoAgente);
            if ($movimento) {
                $request->movimento_portafoglio_id = $movimento->id;
                $request->save();
            }

            $uploadUid = trim((string) ($data['upload_uid'] ?? ''));
            if ($uploadUid !== '') {
                $this->documents->attachPendingByUid($uploadUid, $request);
            }

            $this->audit->log('create', $request, null, [
                'request_number' => $request->request_number,
                'applicant_type' => $request->applicant_type->value,
                'prezzo_cliente' => $prezzoCliente,
                'prezzo_agente' => $prezzoAgente,
            ]);

            return $request->fresh(['subjects', 'checklistItems', 'consents', 'documents']);
        });
    }

    public function updateDraft(SendRequest $request, User $actor, array $data): SendRequest
    {
        if (! $request->status->isEditableByOperator()) {
            throw new InvalidArgumentException('La pratica non è modificabile nello stato attuale.');
        }

        return DB::transaction(function () use ($request, $actor, $data) {
            $request->fill([
                'updated_by' => $actor->id,
                'applicant_type' => $data['applicant_type'] ?? $request->applicant_type->value,
                'priority' => $data['priority'] ?? $request->priority->value,
                'send_notice_identifier' => $data['send_notice_identifier'] ?? $request->send_notice_identifier,
                'iun' => $data['iun'] ?? $request->iun,
                'sender_entity' => $data['sender_entity'] ?? $request->sender_entity,
                'notice_date' => $data['notice_date'] ?? $request->notice_date,
                'received_date' => $data['received_date'] ?? $request->received_date,
                'due_date' => $data['due_date'] ?? $request->due_date,
                'notice_pages' => $data['notice_pages'] ?? $request->notice_pages,
                'communication_type' => $data['communication_type'] ?? $request->communication_type,
                'initial_notes' => $data['initial_notes'] ?? $request->initial_notes,
            ]);
            $request->save();

            if (isset($data['subjects'])) {
                $this->syncSubjects($request, $data['subjects']);
            }
            $this->checklist->syncForRequest($request->fresh());
            if (isset($data['checklist'])) {
                $this->checklist->updateFromInput($request->fresh(), $data['checklist']);
            }
            if (isset($data['consents'])) {
                $this->syncConsents($request, $actor, $data['consents']);
            }

            $uploadUid = trim((string) ($data['upload_uid'] ?? ''));
            if ($uploadUid !== '') {
                $this->documents->attachPendingByUid($uploadUid, $request);
            }

            $this->audit->log('update', $request);

            return $request->fresh(['subjects', 'checklistItems', 'consents', 'documents']);
        });
    }

    /** Salva dati operatore e invia subito al supervisore (create o update bozze/integrazioni). */
    public function saveAndSubmit(User $actor, array $data, ?SendRequest $existing = null): SendRequest
    {
        return DB::transaction(function () use ($actor, $data, $existing) {
            $request = $existing
                ? $this->updateDraft($existing, $actor, $data)
                : $this->createDraft($actor, $data);

            $request = $request->fresh(['subjects', 'checklistItems', 'consents', 'documents']);

            return $this->submit(
                $request,
                $actor,
                isset($data['supervisor_id']) ? ((int) $data['supervisor_id'] ?: null) : null
            );
        });
    }

    public function submit(SendRequest $request, User $actor, ?int $supervisorId = null): SendRequest
    {
        $missing = $this->checklist->missingRequired($request);
        if ($missing) {
            throw ValidationException::withMessages([
                'checklist' => 'Documenti/requisiti mancanti: '.implode('; ', $missing),
            ]);
        }

        $hasPrivacyConsent = $request->consents()
            ->where('consent_type', 'privacy')
            ->where('accepted', true)
            ->exists();
        if (! $hasPrivacyConsent) {
            throw ValidationException::withMessages([
                'consents.privacy' => 'Il consenso privacy è obbligatorio prima dell\'invio.',
            ]);
        }

        $this->assertHasOperatorDocument($request);

        $fromIntegration = $request->status === SendRequestStatus::INTEGRATION_REQUIRED;

        if ($fromIntegration) {
            $request = $this->status->transition($request, SendRequestStatus::RESUBMITTED, $actor);
            if ($request->assigned_supervisor_id) {
                app(SendNotificationService::class)->notifySupervisorResubmitted($request);

                return $request;
            }
        } else {
            $request = $this->status->transition($request, SendRequestStatus::SUBMITTED, $actor);
        }

        $allowManual = SendSetting::getValue('allow_manual_assignment', '1') === '1';
        if ($allowManual && $supervisorId) {
            return $this->assignment->assignOnSubmit($request, $actor, $supervisorId);
        }

        return $this->assignment->assignOnSubmit($request, $actor);
    }

    public function takeCharge(SendRequest $request, User $actor): SendRequest
    {
        return DB::transaction(function () use ($request, $actor) {
            $locked = SendRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->assigned_supervisor_id !== $actor->id) {
                throw new InvalidArgumentException('Pratica non assegnata a questo supervisore.');
            }
            if (! in_array($locked->status, [SendRequestStatus::ASSIGNED, SendRequestStatus::RESUBMITTED], true)) {
                throw new InvalidArgumentException('Stato non valido per presa in carico.');
            }
            $locked = $this->status->transition($locked, SendRequestStatus::TAKEN_IN_CHARGE, $actor);
            try {
                $locked->creator?->notify(new NotificaSendTakenInCharge($locked));
            } catch (\Throwable $e) {
            }

            return $locked;
        });
    }

    public function claim(SendRequest $request, User $actor): SendRequest
    {
        return $this->assignment->claim($request, $actor);
    }

    public function startProcessing(SendRequest $request, User $actor): SendRequest
    {
        $request = $this->status->transition($request, SendRequestStatus::PROCESSING, $actor);

        if (config('send.integration_enabled')) {
            try {
                $result = app(SendProviderInterface::class)->processRequest($request);
                $this->audit->log('provider_process', $request, null, [
                    'ok' => $result->ok,
                    'message' => $result->message,
                    'meta' => $result->meta,
                ]);
            } catch (\Throwable $e) {
                $this->audit->log('provider_process_error', $request, null, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $request;
    }

    public function requestIntegration(SendRequest $request, User $actor, string $reason, ?string $category = null, ?string $dueAt = null): SendRequest
    {
        $request->integration_category = $category;
        $request->integration_due_at = $dueAt;
        $request->save();
        $request = $this->status->transition($request, SendRequestStatus::INTEGRATION_REQUIRED, $actor, $reason, [
            'category' => $category,
        ]);
        try {
            $request->creator?->notify(new NotificaSendIntegrationRequired($request));
        } catch (\Throwable $e) {
        }

        return $request;
    }

    public function complete(SendRequest $request, User $actor, ?string $note = null): SendRequest
    {
        if (! $request->documentsForClient()->exists()) {
            throw ValidationException::withMessages([
                'documents' => 'Caricare l\'allegato SEND per il cliente prima di completare la pratica.',
            ]);
        }

        if ($note) {
            $this->addNote($request, $actor, $note, 'operator', 'operative');
        }
        $request = $this->status->transition($request, SendRequestStatus::COMPLETED, $actor);
        try {
            $request->creator?->notify(new NotificaSendCompleted($request));
        } catch (\Throwable $e) {
        }

        return $request;
    }

    /** Dopo upload allegato cliente: avanza stati intermedi e completa la pratica. */
    public function completeAfterClientDocumentUpload(SendRequest $request, User $actor): SendRequest
    {
        if (in_array($request->status, [
            SendRequestStatus::COMPLETED,
            SendRequestStatus::DELIVERED,
            SendRequestStatus::CLOSED,
        ], true)) {
            return $request;
        }

        if (! $request->documentsForClient()->exists()) {
            throw ValidationException::withMessages([
                'documents' => 'Caricare l\'allegato SEND per il cliente.',
            ]);
        }

        return DB::transaction(function () use ($request, $actor) {
            $locked = SendRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [
                SendRequestStatus::COMPLETED,
                SendRequestStatus::DELIVERED,
                SendRequestStatus::CLOSED,
            ], true)) {
                return $locked;
            }

            if ($locked->status === SendRequestStatus::ASSIGNED) {
                $locked = $this->status->transition($locked, SendRequestStatus::TAKEN_IN_CHARGE, $actor);
            }

            if ($locked->status === SendRequestStatus::RESUBMITTED) {
                $locked = $this->status->transition($locked, SendRequestStatus::PROCESSING, $actor);
            } elseif ($locked->status === SendRequestStatus::TAKEN_IN_CHARGE) {
                $locked = $this->status->transition($locked, SendRequestStatus::PROCESSING, $actor);
            }

            if ($locked->status === SendRequestStatus::PROCESSING) {
                $locked = $this->status->transition($locked, SendRequestStatus::COMPLETED, $actor);
                try {
                    $locked->creator?->notify(new NotificaSendCompleted($locked));
                } catch (\Throwable $e) {
                }
            }

            return $locked->fresh();
        });
    }

    public function reject(SendRequest $request, User $actor, string $reason): SendRequest
    {
        return DB::transaction(function () use ($request, $actor, $reason) {
            $this->refundPlafondIfNeeded($request, $actor, $reason);
            $request = $this->status->transition($request, SendRequestStatus::REJECTED, $actor, $reason);
            try {
                $request->creator?->notify(new NotificaSendRejected($request));
            } catch (\Throwable $e) {
            }

            return $request;
        });
    }

    public function cancel(SendRequest $request, User $actor, string $reason): SendRequest
    {
        return DB::transaction(function () use ($request, $actor, $reason) {
            $this->refundPlafondIfNeeded($request, $actor, $reason);
            $request = $this->status->transition($request, SendRequestStatus::CANCELLED, $actor, $reason);
            try {
                $request->creator?->notify(new NotificaSendCancelled($request));
            } catch (\Throwable $e) {
            }

            return $request;
        });
    }

    public function destroyDraft(SendRequest $request, User $actor): void
    {
        if ($request->status !== SendRequestStatus::DRAFT) {
            throw new InvalidArgumentException('Solo le bozze possono essere eliminate.');
        }

        DB::transaction(function () use ($request, $actor) {
            $this->refundPlafondIfNeeded($request, $actor, 'Eliminazione bozza');
            $this->audit->log('destroy', $request);
            $request->delete();
        });
    }

    public function reopen(SendRequest $request, User $actor, ?string $reason = null): SendRequest
    {
        if (! in_array($request->status, [
            SendRequestStatus::REJECTED,
            SendRequestStatus::CANCELLED,
            SendRequestStatus::EXPIRED,
        ], true)) {
            throw new InvalidArgumentException('Solo pratiche rifiutate, annullate o scadute possono essere riaperte.');
        }

        return DB::transaction(function () use ($request, $actor, $reason) {
            $request = $this->status->transition($request, SendRequestStatus::DRAFT, $actor, $reason ?: 'Riapertura pratica');
            $request->rejection_reason = null;
            $request->cancellation_reason = null;
            $request->rejected_at = null;
            $request->cancelled_at = null;
            $request->assigned_supervisor_id = null;
            $request->assigned_at = null;
            $request->taken_in_charge_at = null;
            $request->processing_started_at = null;
            $request->completed_at = null;
            $request->save();
            $this->audit->log('reopen', $request);

            return $request->fresh();
        });
    }

    public function prezzoCliente(): float
    {
        $fromSettings = SendSetting::getValue('prezzo_cliente');
        if ($fromSettings !== null && $fromSettings !== '') {
            return (float) $fromSettings;
        }

        return (float) config('send.prezzo_cliente', 5);
    }

    public function prezzoAgente(): float
    {
        $fromSettings = SendSetting::getValue('prezzo_agente');
        if ($fromSettings !== null && $fromSettings !== '') {
            return (float) $fromSettings;
        }

        return (float) config('send.prezzo_agente', 4);
    }

    public function importoFornitore(): float
    {
        $fromSettings = SendSetting::getValue('importo_fornitore');
        if ($fromSettings !== null && $fromSettings !== '') {
            return (float) $fromSettings;
        }

        return (float) config('send.importo_fornitore', 0);
    }

    public function portafoglioServiziDi(User $user): float
    {
        $agente = Agente::query()->firstWhere('user_id', $user->id);

        return (float) ($agente->portafoglio_servizi ?? 0);
    }

    private function assertSufficientPlafond(User $actor, float $amount): void
    {
        $agente = Agente::query()->firstWhere('user_id', $actor->id);
        if (! $agente) {
            // Admin/backoffice senza profilo agente: nessun addebito possibile
            return;
        }

        if ((float) $agente->portafoglio_servizi < $amount) {
            throw ValidationException::withMessages([
                'plafond' => 'Plafond servizi insufficiente: servono '.number_format($amount, 2, ',', '.').' € (disponibili '.number_format((float) $agente->portafoglio_servizi, 2, ',', '.').' €).',
            ]);
        }
    }

    private function chargePlafond(SendRequest $request, User $actor, float $amount): ?MovimentoPortafoglio
    {
        if ($amount <= 0) {
            return null;
        }

        $agente = Agente::query()->firstWhere('user_id', $actor->id);
        if (! $agente) {
            return null;
        }

        $movimento = new MovimentoPortafoglio;
        $movimento->agente_id = $actor->id;
        $movimento->importo = -$amount;
        $movimento->descrizione = 'Pratica SEND '.$request->request_number;
        $movimento->prodotto_id = $request->id;
        $movimento->prodotto_type = SendRequest::class;
        $movimento->portafoglio = TipiPortafoglioEnum::SERVIZI->value;
        $movimento->save();

        return $movimento;
    }

    private function refundPlafondIfNeeded(SendRequest $request, User $actor, string $reason): void
    {
        if (! $request->movimento_portafoglio_id || (float) $request->prezzo_agente <= 0) {
            return;
        }

        $alreadyRefunded = MovimentoPortafoglio::withoutGlobalScope('filtroOperatore')
            ->where('prodotto_type', SendRequest::class)
            ->where('prodotto_id', $request->id)
            ->where('importo', '>', 0)
            ->exists();

        if ($alreadyRefunded) {
            return;
        }

        $agente = Agente::query()->firstWhere('user_id', $request->created_by);
        if (! $agente) {
            return;
        }

        $movimento = new MovimentoPortafoglio;
        $movimento->agente_id = $request->created_by;
        $movimento->importo = (float) $request->prezzo_agente;
        $movimento->descrizione = 'Rimborso SEND '.$request->request_number.($reason ? ' ('.$reason.')' : '');
        $movimento->prodotto_id = $request->id;
        $movimento->prodotto_type = SendRequest::class;
        $movimento->portafoglio = TipiPortafoglioEnum::SERVIZI->value;
        $movimento->save();

        $this->audit->log('plafond_refund', $request, null, [
            'importo' => $request->prezzo_agente,
        ], $reason);
    }

    /**
     * @return array{from:?string,to:?string,by_status:array<string,int>,totals:array{count:int,prezzo_cliente:float,prezzo_agente:float},rows:\Illuminate\Support\Collection}
     */
    public function reportData(User $user, array $filters): array
    {
        $q = $this->scopedQuery($user);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        $byStatus = [];
        foreach (SendRequestStatus::cases() as $status) {
            $byStatus[$status->value] = (clone $q)->where('status', $status->value)->count();
        }

        $rows = (clone $q)
            ->with(['creator', 'supervisor', 'subjects'])
            ->latest('id')
            ->limit(500)
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'by_status' => $byStatus,
            'totals' => [
                'count' => (clone $q)->count(),
                'prezzo_cliente' => (float) (clone $q)->sum('prezzo_cliente'),
                'prezzo_agente' => (float) (clone $q)->sum('prezzo_agente'),
            ],
            'rows' => $rows,
        ];
    }

    public function deliver(SendRequest $request, User $actor, array $data): SendRequest
    {
        return DB::transaction(function () use ($request, $actor, $data) {
            SendRequestDelivery::query()->create([
                'send_request_id' => $request->id,
                'delivered_by' => $actor->id,
                'recipient_type' => $data['recipient_type'] ?? 'cittadino',
                'recipient_name' => $data['recipient_name'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? 'sportello',
                'identification_type' => $data['identification_type'] ?? null,
                'document_verified' => $data['document_verified'] ?? null,
                'delivered_at' => $data['delivered_at'] ?? now(),
                'documents_summary' => $data['documents_summary'] ?? null,
                'confirmation_data' => $data['confirmation_data'] ?? null,
                'print_done' => (bool) ($data['print_done'] ?? false),
                'notes' => $data['notes'] ?? null,
            ]);

            $request = $this->status->transition($request, SendRequestStatus::DELIVERED, $actor);
            $this->audit->log('deliver', $request);

            $closed = $this->status->transition($request, SendRequestStatus::CLOSED, $actor);
            try {
                $closed->creator?->notify(new NotificaSendDelivered($closed));
            } catch (\Throwable $e) {
            }

            return $closed;
        });
    }

    public function addNote(SendRequest $request, User $actor, string $body, string $visibility = 'operator', string $type = 'operative'): SendRequestNote
    {
        $note = $request->notes()->create([
            'author_id' => $actor->id,
            'note_type' => $type,
            'visibility' => $visibility,
            'body' => $body,
        ]);
        $this->audit->log('note_create', $request, null, ['note_id' => $note->id, 'visibility' => $visibility]);

        return $note;
    }

    /** KPI coda personale supervisore (dashboard + widget). */
    public function supervisorOperativoForDashboard(User $user): array
    {
        $mineBase = SendRequest::query()->where('assigned_supervisor_id', $user->id);

        $toTake = (clone $mineBase)->whereIn('status', [
            SendRequestStatus::ASSIGNED->value,
            SendRequestStatus::RESUBMITTED->value,
        ])->count();

        $inWork = (clone $mineBase)->whereIn('status', [
            SendRequestStatus::TAKEN_IN_CHARGE->value,
            SendRequestStatus::PROCESSING->value,
        ])->count();

        $urgent = (clone $mineBase)->where('priority', SendPriority::URGENTE->value)
            ->whereNotIn('status', [
                SendRequestStatus::CLOSED->value,
                SendRequestStatus::CANCELLED->value,
                SendRequestStatus::REJECTED->value,
                SendRequestStatus::DELIVERED->value,
                SendRequestStatus::COMPLETED->value,
            ])->count();

        $completedToday = (clone $mineBase)->where('status', SendRequestStatus::COMPLETED->value)
            ->whereDate('completed_at', today())->count();

        $assignedQueue = (clone $mineBase)
            ->with(['creator', 'subjects'])
            ->whereIn('status', [
                SendRequestStatus::ASSIGNED->value,
                SendRequestStatus::RESUBMITTED->value,
                SendRequestStatus::TAKEN_IN_CHARGE->value,
                SendRequestStatus::PROCESSING->value,
            ])
            ->orderByRaw("CASE
                WHEN priority = 'urgente' THEN 0
                WHEN status IN ('assigned','resubmitted') THEN 1
                ELSE 2 END")
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingAssignment = 0;
        $unassignedPool = collect();
        if ($user->can('send.requests.view-all') || $user->can('admin')) {
            $pendingAssignment = SendRequest::query()
                ->whereNull('assigned_supervisor_id')
                ->whereIn('status', [
                    SendRequestStatus::SUBMITTED->value,
                    SendRequestStatus::AWAITING_ASSIGNMENT->value,
                ])
                ->count();

            $unassignedPool = SendRequest::query()
                ->with(['creator', 'subjects'])
                ->whereNull('assigned_supervisor_id')
                ->whereIn('status', [
                    SendRequestStatus::SUBMITTED->value,
                    SendRequestStatus::AWAITING_ASSIGNMENT->value,
                ])
                ->latest('id')
                ->limit(8)
                ->get();
        }

        $prossime = $assignedQueue->merge($unassignedPool)->unique('id')->take(8)->values();

        return [
            'to_take' => $toTake,
            'in_work' => $inWork,
            'urgent' => $urgent,
            'completed_today' => $completedToday,
            'pending_assignment' => $pendingAssignment,
            'prossime' => $prossime,
        ];
    }

    public function stats(User $user): array
    {
        $base = $this->scopedQuery($user);

        $byStatus = [];
        foreach (SendRequestStatus::cases() as $status) {
            $byStatus[$status->value] = (clone $base)->where('status', $status->value)->count();
        }

        $openStatuses = [
            SendRequestStatus::SUBMITTED->value,
            SendRequestStatus::AWAITING_ASSIGNMENT->value,
            SendRequestStatus::ASSIGNED->value,
            SendRequestStatus::TAKEN_IN_CHARGE->value,
            SendRequestStatus::PROCESSING->value,
            SendRequestStatus::INTEGRATION_REQUIRED->value,
            SendRequestStatus::RESUBMITTED->value,
        ];

        $urgent = (clone $base)->where('priority', SendPriority::URGENTE->value)
            ->whereNotIn('status', [
                SendRequestStatus::CLOSED->value,
                SendRequestStatus::CANCELLED->value,
                SendRequestStatus::REJECTED->value,
                SendRequestStatus::DELIVERED->value,
            ])->count();

        $today = (clone $base)->whereDate('created_at', today())->count();
        $completedToday = (clone $base)->where('status', SendRequestStatus::COMPLETED->value)
            ->whereDate('completed_at', today())->count();
        $open = (clone $base)->whereIn('status', $openStatuses)->count();
        $awaitingAssignment = (clone $base)->whereIn('status', [
            SendRequestStatus::SUBMITTED->value,
            SendRequestStatus::AWAITING_ASSIGNMENT->value,
        ])->count();

        $recent = (clone $base)
            ->with(['creator', 'supervisor', 'subjects'])
            ->latest('id')
            ->limit(8)
            ->get();

        $readyToDeliver = $byStatus[SendRequestStatus::COMPLETED->value] ?? 0;
        $integration = $byStatus[SendRequestStatus::INTEGRATION_REQUIRED->value] ?? 0;

        $isAdminView = $user->can('admin');
        $isSupervisorView = ! $isAdminView && $user->can('send.requests.process');
        $isAgentView = ! $isAdminView && ! $isSupervisorView;

        $supervisorOperativo = $this->supervisorOperativoForDashboard($user);
        $toTakeCharge = $supervisorOperativo['to_take'];
        $inMyWork = $supervisorOperativo['in_work'];
        $myUrgent = $supervisorOperativo['urgent'];
        $myCompletedToday = $supervisorOperativo['completed_today'];
        $pendingAssignment = $supervisorOperativo['pending_assignment'] ?? 0;
        $supervisorRecent = $supervisorOperativo['prossime'];
        $mineBase = SendRequest::query()->where('assigned_supervisor_id', $user->id);

        $prezzoCliente = $this->prezzoCliente();
        $prezzoAgente = $this->prezzoAgente();

        $plafondServizi = null;
        $agente = Agente::query()->firstWhere('user_id', $user->id);
        if ($agente) {
            $plafondServizi = (float) ($agente->portafoglio_servizi ?? 0);
        }

        if ($isSupervisorView) {
            $kpis = [
                ['key' => 'take', 'label' => 'Da prendere in carico', 'value' => $toTakeCharge],
                ['key' => 'work', 'label' => 'In lavorazione', 'value' => $inMyWork],
                ['key' => 'urgent', 'label' => 'Urgenti (mie)', 'value' => $myUrgent],
                ['key' => 'today', 'label' => 'Completate oggi', 'value' => $myCompletedToday],
            ];
            if ($user->can('send.requests.view-all') || $user->can('admin')) {
                $kpis[] = ['key' => 'pending', 'label' => 'Senza supervisore', 'value' => $pendingAssignment];
            }
            $pipeline = [
                ['key' => 'assigned', 'label' => 'Assegnate a me', 'value' => $toTakeCharge, 'status' => SendRequestStatus::ASSIGNED->value],
                ['key' => 'processing', 'label' => 'In lavorazione', 'value' => $inMyWork, 'status' => SendRequestStatus::PROCESSING->value],
                ['key' => 'integration', 'label' => 'Integrazione richiesta', 'value' => (clone $mineBase)->where('status', SendRequestStatus::INTEGRATION_REQUIRED->value)->count(), 'status' => SendRequestStatus::INTEGRATION_REQUIRED->value],
                ['key' => 'completed', 'label' => 'Completate', 'value' => (clone $mineBase)->where('status', SendRequestStatus::COMPLETED->value)->count(), 'status' => SendRequestStatus::COMPLETED->value],
            ];
            if ($user->can('send.requests.view-all') || $user->can('admin')) {
                $pipeline[] = ['key' => 'pending', 'label' => 'Senza supervisore', 'value' => $pendingAssignment, 'status' => SendRequestStatus::AWAITING_ASSIGNMENT->value];
            }
            $recentForView = $supervisorRecent;
        } elseif ($isAgentView) {
            $kpis = [
                ['key' => 'open', 'label' => 'Mie aperte', 'value' => $open],
                ['key' => 'integration', 'label' => 'Da integrare', 'value' => $integration],
                ['key' => 'deliver', 'label' => 'Da consegnare', 'value' => $readyToDeliver],
                ['key' => 'today', 'label' => 'Create oggi', 'value' => $today],
            ];
            $pipeline = [
                ['key' => 'draft', 'label' => 'Bozze', 'value' => $byStatus[SendRequestStatus::DRAFT->value] ?? 0, 'status' => SendRequestStatus::DRAFT->value],
                ['key' => 'queue', 'label' => 'In coda', 'value' => $awaitingAssignment, 'status' => SendRequestStatus::AWAITING_ASSIGNMENT->value],
                ['key' => 'assigned', 'label' => 'Assegnate', 'value' => $byStatus[SendRequestStatus::ASSIGNED->value] ?? 0, 'status' => SendRequestStatus::ASSIGNED->value],
                ['key' => 'processing', 'label' => 'In lavorazione', 'value' => ($byStatus[SendRequestStatus::PROCESSING->value] ?? 0) + ($byStatus[SendRequestStatus::TAKEN_IN_CHARGE->value] ?? 0), 'status' => SendRequestStatus::PROCESSING->value],
                ['key' => 'integration', 'label' => 'Integrazione', 'value' => $integration, 'status' => SendRequestStatus::INTEGRATION_REQUIRED->value],
                ['key' => 'completed', 'label' => 'Da consegnare', 'value' => $readyToDeliver, 'status' => SendRequestStatus::COMPLETED->value],
            ];
            $recentForView = $recent;
        } else {
            $kpis = [
                ['key' => 'open', 'label' => 'Pratiche aperte', 'value' => $open],
                ['key' => 'urgent', 'label' => 'Urgenti', 'value' => $urgent],
                ['key' => 'integration', 'label' => 'Da integrare', 'value' => $integration],
                ['key' => 'today', 'label' => 'Create oggi', 'value' => $today],
            ];
            $pipeline = [
                ['key' => 'draft', 'label' => 'Bozze', 'value' => $byStatus[SendRequestStatus::DRAFT->value] ?? 0, 'status' => SendRequestStatus::DRAFT->value],
                ['key' => 'queue', 'label' => 'In coda', 'value' => $awaitingAssignment, 'status' => SendRequestStatus::AWAITING_ASSIGNMENT->value],
                ['key' => 'assigned', 'label' => 'Assegnate', 'value' => $byStatus[SendRequestStatus::ASSIGNED->value] ?? 0, 'status' => SendRequestStatus::ASSIGNED->value],
                ['key' => 'processing', 'label' => 'In lavorazione', 'value' => ($byStatus[SendRequestStatus::PROCESSING->value] ?? 0) + ($byStatus[SendRequestStatus::TAKEN_IN_CHARGE->value] ?? 0), 'status' => SendRequestStatus::PROCESSING->value],
                ['key' => 'integration', 'label' => 'Integrazione', 'value' => $integration, 'status' => SendRequestStatus::INTEGRATION_REQUIRED->value],
                ['key' => 'completed', 'label' => 'Completate', 'value' => $readyToDeliver, 'status' => SendRequestStatus::COMPLETED->value],
            ];
            $recentForView = $recent;
        }

        return [
            // legacy flat keys (compat)
            'draft' => $byStatus[SendRequestStatus::DRAFT->value] ?? 0,
            'submitted' => $byStatus[SendRequestStatus::SUBMITTED->value] ?? 0,
            'assigned' => $byStatus[SendRequestStatus::ASSIGNED->value] ?? 0,
            'processing' => $byStatus[SendRequestStatus::PROCESSING->value] ?? 0,
            'integration_required' => $integration,
            'completed' => $readyToDeliver,
            'urgent' => $urgent,
            'total' => (clone $base)->count(),
            'by_status' => $byStatus,
            'is_agent_view' => $isAgentView,
            'is_supervisor_view' => $isSupervisorView,
            'is_admin_view' => $isAdminView,
            'supervisor' => [
                'to_take_charge' => $toTakeCharge,
                'in_work' => $inMyWork,
                'urgent' => $myUrgent,
                'completed_today' => $myCompletedToday,
                'pending_assignment' => $pendingAssignment,
            ],
            'kpis' => $kpis,
            'pipeline' => $pipeline,
            'pricing' => [
                'prezzo_cliente' => $prezzoCliente,
                'prezzo_agente' => $prezzoAgente,
                'completed_today' => $isSupervisorView ? $myCompletedToday : $completedToday,
                'plafond_servizi' => $plafondServizi,
            ],
            'recent' => $recentForView,
        ];
    }

    public function list(User $user, array $filters, int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $q = $this->scopedQuery($user)
            ->with(['creator', 'supervisor', 'subjects', 'documentsForClient'])
            ->withCount('documentsForClient');

        if (! empty($filters['status'])) {
            $statuses = array_filter(array_map('trim', explode(',', (string) $filters['status'])));
            if (count($statuses) === 1) {
                $q->where('status', $statuses[0]);
            } elseif (count($statuses) > 1) {
                $q->whereIn('status', $statuses);
            }
        }
        if (! empty($filters['priority'])) {
            $q->where('priority', $filters['priority']);
        }
        if (! empty($filters['applicant_type'])) {
            $q->where('applicant_type', $filters['applicant_type']);
        }
        if (! empty($filters['created_by'])) {
            $q->where('created_by', $filters['created_by']);
        }
        if (! empty($filters['assigned_supervisor_id'])) {
            $q->where('assigned_supervisor_id', $filters['assigned_supervisor_id']);
        }
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function ($w) use ($term) {
                $w->where('request_number', 'like', $term)
                    ->orWhere('send_notice_identifier', 'like', $term)
                    ->orWhere('iun', 'like', $term)
                    ->orWhereHas('subjects', function ($s) use ($term) {
                        $s->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('business_name', 'like', $term)
                            ->orWhere('tax_code', 'like', $term);
                    });
            });
        }
        if (! empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        return $q->latest('id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function scopedQuery(User $user)
    {
        $q = SendRequest::query();

        if ($user->can('admin') || $user->can('send.requests.view-all')) {
            return $q;
        }

        return $q->where(function ($w) use ($user) {
            $w->where('created_by', $user->id);
            if ($user->can('send.requests.process')) {
                $w->orWhere('assigned_supervisor_id', $user->id);
            }
        });
    }

    private function syncSubjects(SendRequest $request, array $subjects): void
    {
        foreach ($subjects as $role => $payload) {
            if (! is_array($payload)) {
                continue;
            }
            $request->subjects()->updateOrCreate(
                ['subject_role' => $role],
                array_merge($payload, ['subject_role' => $role])
            );
        }
    }

    private function syncConsents(SendRequest $request, User $actor, array $consents): void
    {
        $privacyVersion = SendSetting::getValue('privacy_version', config('send.privacy_version'));
        foreach ($consents as $type => $accepted) {
            if (! $accepted) {
                continue;
            }
            SendRequestConsent::query()->updateOrCreate(
                ['send_request_id' => $request->id, 'consent_type' => $type],
                [
                    'privacy_version' => $privacyVersion,
                    'accepted' => true,
                    'accepted_by' => $actor->id,
                    'accepted_at' => now(),
                ]
            );
        }
    }

    private function assertHasOperatorDocument(SendRequest $request): void
    {
        $has = $request->documents()
            ->where('visibility', '!=', 'citizen_receipt')
            ->whereNotIn('category', ['risultato', 'ricevuta'])
            ->exists();

        if (! $has) {
            throw ValidationException::withMessages([
                'documents' => 'Caricare almeno un allegato operatore prima dell\'invio.',
            ]);
        }
    }
}

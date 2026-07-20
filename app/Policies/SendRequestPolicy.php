<?php

namespace App\Policies;

use App\Enums\SendDocumentCategory;
use App\Enums\SendNoteVisibility;
use App\Enums\SendRequestStatus;
use App\Models\SendRequest;
use App\Models\SendRequestDocument;
use App\Models\User;

class SendRequestPolicy
{
    public const MODULE = 'servizio_send';

    /** @var list<string> */
    private const SUPERVISOR_ONLY_CATEGORIES = ['risultato', 'ricevuta'];

    public function viewAny(User $user): bool
    {
        if ($user->can('admin')) {
            return true;
        }

        return $user->can(self::MODULE);
    }

    public function view(User $user, SendRequest $request): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->can('admin') || $user->can('send.requests.view-all')) {
            return true;
        }

        if ($request->assigned_supervisor_id === $user->id && $user->can('send.requests.process')) {
            return true;
        }

        if ($user->can('send.requests.view-own') && $request->created_by === $user->id) {
            return true;
        }

        if ($user->can(self::MODULE) && $request->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        if ($user->can('admin')) {
            return true;
        }

        return $user->can('send.requests.create');
    }

    public function update(User $user, SendRequest $request): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        if (! $this->view($user, $request) || ! $user->can('send.requests.update')) {
            return false;
        }

        return $request->status->isEditableByOperator();
    }

    public function useOperatorForm(User $user, ?SendRequest $request = null): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        if (! $user->can('create', SendRequest::class) && ! ($request && $user->can('update', $request))) {
            return false;
        }

        if ($request === null) {
            return true;
        }

        return $request->status->isEditableByOperator()
            && ($user->can('admin') || $request->created_by === $user->id);
    }

    public function delete(User $user, SendRequest $request): bool
    {
        return $user->can('send.requests.delete')
            && $request->status === SendRequestStatus::DRAFT
            && ($user->can('admin') || $request->created_by === $user->id);
    }

    public function submit(User $user, SendRequest $request): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        return $this->view($user, $request)
            && $user->can('send.requests.submit')
            && in_array($request->status, [SendRequestStatus::DRAFT, SendRequestStatus::INTEGRATION_REQUIRED], true);
    }

    public function assign(User $user, SendRequest $request): bool
    {
        if (! $this->view($user, $request) || ! $user->can('send.requests.assign')) {
            return false;
        }

        return in_array($request->status, [
            SendRequestStatus::AWAITING_ASSIGNMENT,
            SendRequestStatus::ASSIGNED,
            SendRequestStatus::TAKEN_IN_CHARGE,
        ], true);
    }

    public function takeCharge(User $user, SendRequest $request): bool
    {
        return $user->can('send.requests.take-charge')
            && $request->assigned_supervisor_id === $user->id
            && in_array($request->status, [SendRequestStatus::ASSIGNED, SendRequestStatus::RESUBMITTED], true);
    }

    public function claim(User $user, SendRequest $request): bool
    {
        if (! $user->can('send.requests.process') || ! $this->view($user, $request)) {
            return false;
        }

        if (! in_array($request->status, [SendRequestStatus::SUBMITTED, SendRequestStatus::AWAITING_ASSIGNMENT], true)) {
            return false;
        }

        return $request->assigned_supervisor_id === null || $request->assigned_supervisor_id === $user->id;
    }

    public function process(User $user, SendRequest $request): bool
    {
        return $user->can('send.requests.process')
            && $request->assigned_supervisor_id === $user->id;
    }

    public function startProcessing(User $user, SendRequest $request): bool
    {
        return $this->process($user, $request)
            && $request->status === SendRequestStatus::TAKEN_IN_CHARGE;
    }

    public function requestIntegration(User $user, SendRequest $request): bool
    {
        return $this->process($user, $request)
            && $user->can('send.requests.request-integration')
            && $request->status === SendRequestStatus::PROCESSING;
    }

    public function complete(User $user, SendRequest $request): bool
    {
        return $this->process($user, $request)
            && $user->can('send.requests.complete')
            && $request->status === SendRequestStatus::PROCESSING;
    }

    public function reject(User $user, SendRequest $request): bool
    {
        return $this->process($user, $request)
            && $user->can('send.requests.reject')
            && $request->status === SendRequestStatus::PROCESSING;
    }

    public function reopen(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request)
            && $user->can('send.requests.reopen')
            && in_array($request->status, [
                SendRequestStatus::REJECTED,
                SendRequestStatus::CANCELLED,
                SendRequestStatus::EXPIRED,
            ], true);
    }

    public function cancel(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request)
            && $user->can('send.requests.cancel')
            && ! $request->status->isTerminal();
    }

    public function deliver(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request)
            && $request->status === SendRequestStatus::COMPLETED
            && ($user->can('send.requests.update') || $user->can('admin'));
    }

    public function viewDocument(User $user, SendRequestDocument $document): bool
    {
        $request = $document->request;
        if (! $request || ! $this->view($user, $request) || ! $user->can('send.documents.view')) {
            return false;
        }

        if ($document->visibility === 'supervisor' && ! $user->can('send.notes.view-internal') && ! $user->can('admin')) {
            return false;
        }

        return true;
    }

    public function downloadDocument(User $user, SendRequestDocument $document): bool
    {
        return $this->viewDocument($user, $document) && $user->can('send.documents.download');
    }

    public function uploadDocument(User $user, SendRequest $request): bool
    {
        return $this->uploadOperatorDocument($user, $request);
    }

    /** Allegati operatore (avviso, identità, delega): non per supervisore-only. */
    public function uploadOperatorDocument(User $user, ?SendRequest $request = null): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        if ($request === null) {
            return $user->can('create', SendRequest::class) || $user->can('send.documents.upload');
        }

        if (! $this->view($user, $request) || ! $user->can('send.documents.upload')) {
            return false;
        }

        if ($request->status->isEditableByOperator()) {
            return $user->can('admin') || $request->created_by === $user->id;
        }

        return $user->can('admin');
    }

    /** Allegato risultato SEND per il cliente: solo supervisore assegnato. */
    public function uploadClientDocument(User $user, SendRequest $request): bool
    {
        if (! $this->view($user, $request) || ! $user->can('send.documents.upload')) {
            return false;
        }

        return $this->process($user, $request)
            && in_array($request->status, [
                SendRequestStatus::ASSIGNED,
                SendRequestStatus::TAKEN_IN_CHARGE,
                SendRequestStatus::PROCESSING,
                SendRequestStatus::RESUBMITTED,
                SendRequestStatus::COMPLETED,
            ], true);
    }

    public function canUploadOperatorCategory(User $user, string $category, ?string $visibility = null): bool
    {
        if ($this->isSupervisorOnly($user)) {
            return false;
        }

        if ($visibility === 'citizen_receipt') {
            return false;
        }

        if (in_array($category, self::SUPERVISOR_ONLY_CATEGORIES, true)) {
            return false;
        }

        $enum = SendDocumentCategory::tryFrom($category);

        return ! in_array($enum, [SendDocumentCategory::RISULTATO, SendDocumentCategory::RICEUTA], true);
    }

    public function downloadClientDocument(User $user, SendRequest $request): bool
    {
        if (! $this->view($user, $request) || ! $user->can('send.documents.download')) {
            return false;
        }

        if (isset($request->documents_for_client_count)) {
            return (int) $request->documents_for_client_count > 0;
        }

        return $request->documentsForClient()->exists();
    }

    public function viewInternalNotes(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request) && $user->can('send.notes.view-internal');
    }

    public function createInternalNote(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request) && $user->can('send.notes.create-internal');
    }

    public function viewAudit(User $user, SendRequest $request): bool
    {
        return $this->view($user, $request) && $user->can('send.audit.view');
    }

    public function manageSettings(User $user): bool
    {
        return $user->can('admin') || ($this->hasModule($user) && $user->can('send.settings.manage'));
    }

    public function viewReports(User $user): bool
    {
        return $user->can('admin') || ($this->hasModule($user) && $user->can('send.reports.view'));
    }

    public function canSeeNote(User $user, SendRequest $request, SendNoteVisibility $visibility): bool
    {
        if (! $this->view($user, $request)) {
            return false;
        }

        if ($visibility === SendNoteVisibility::INTERNAL) {
            return $this->viewInternalNotes($user, $request);
        }

        return true;
    }

    public function isSupervisorOnly(User $user): bool
    {
        if ($user->can('admin') || $user->can('send.requests.create')) {
            return false;
        }

        return $user->can('send.requests.process');
    }

    private function hasModule(User $user): bool
    {
        return $user->can(self::MODULE) || $user->can('admin');
    }
}

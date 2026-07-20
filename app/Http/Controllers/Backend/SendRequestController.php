<?php

namespace App\Http\Controllers\Backend;

use App\Enums\SendApplicantType;
use App\Enums\SendDocumentCategory;
use App\Enums\SendNoteVisibility;
use App\Enums\SendPriority;
use App\Enums\SendRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Send\SendWorkflowActionRequest;
use App\Http\Requests\Send\StoreSendRequestRequest;
use App\Http\Requests\Send\UploadSendClientDocumentRequest;
use App\Http\Requests\Send\UploadSendDocumentRequest;
use App\Http\Services\SendAssignmentService;
use App\Http\Services\SendAuditService;
use App\Http\Services\SendChecklistService;
use App\Http\Services\SendDocumentService;
use App\Http\Services\SendRequestService;
use App\Models\SendRequest;
use App\Models\SendRequestDocument;
use App\Models\SendSetting;
use App\Models\User;
use App\Policies\SendRequestPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SendRequestController extends Controller
{
    public function __construct(
        private SendRequestService $service,
        private SendAssignmentService $assignment,
        private SendDocumentService $documents,
        private SendChecklistService $checklist,
        private SendAuditService $audit,
        private SendRequestPolicy $sendPolicy,
    ) {
        $this->middleware('can:viewAny,'.SendRequest::class);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $stats = $this->service->stats($user);
        $isAgentView = (bool) ($stats['is_agent_view'] ?? false);
        $isSupervisorView = (bool) ($stats['is_supervisor_view'] ?? false);

        $titolo = 'SEND — Dashboard';
        if ($isAgentView) {
            $titolo = 'SEND — Le mie pratiche';
        } elseif ($isSupervisorView) {
            $titolo = 'SEND — Coda supervisore';
        }

        return view('Backend.Send.dashboard', [
            'stats' => $stats,
            'titoloPagina' => $titolo,
            'controller' => self::class,
        ]);
    }

    public function index(Request $request)
    {
        $records = $this->service->list(
            Auth::user(),
            $request->only([
                'status', 'priority', 'applicant_type', 'created_by',
                'assigned_supervisor_id', 'q', 'from', 'to',
            ]),
            (int) $request->get('page', 1),
            (int) config('configurazione.paginazione', 25),
        );
        $records->appends($request->query());

        return view('Backend.Send.index', [
            'records' => $records,
            'filters' => $request->all(),
            'statuses' => SendRequestStatus::cases(),
            'priorities' => SendPriority::cases(),
            'applicantTypes' => SendApplicantType::cases(),
            'titoloPagina' => 'SEND — Richieste',
            'controller' => self::class,
            'audit' => $this->audit,
        ]);
    }

    public function create()
    {
        $this->authorize('create', SendRequest::class);
        $user = Auth::user();
        $uploadUid = (string) \Illuminate\Support\Str::ulid();

        return view('Backend.Send.form-operator', [
            'record' => null,
            'uploadUid' => $uploadUid,
            'applicantTypes' => SendApplicantType::cases(),
            'priorities' => SendPriority::cases(),
            'checklistLabels' => SendChecklistService::LABELS,
            'supervisors' => $this->assignment->eligibleSupervisors(),
            'documentCategories' => SendDocumentCategory::cases(),
            'prezzoCliente' => $this->service->prezzoCliente(),
            'prezzoAgente' => $this->service->prezzoAgente(),
            'portafoglioServizi' => $this->service->portafoglioServiziDi($user),
            'titoloPagina' => 'SEND — Nuova richiesta',
            'controller' => self::class,
        ]);
    }

    public function store(StoreSendRequestRequest $request)
    {
        try {
            $record = $this->service->saveAndSubmit(Auth::user(), $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $supervisorName = $record->supervisor?->nominativo() ?: 'supervisore';
        $msg = 'Pratica inviata al supervisore '.$supervisorName.'. Codice '.$record->request_number.'.';
        if ($record->movimento_portafoglio_id) {
            $msg .= ' Scalati '.importo($record->prezzo_agente, true).' dal plafond servizi.';
        }

        return redirect()->action([self::class, 'show'], $record)
            ->with('success', $msg);
    }

    public function show(SendRequest $send)
    {
        $this->authorize('view', $send);
        $send->load([
            'subjects', 'checklistItems', 'documents', 'notes.author',
            'statusHistory.changer', 'assignments.supervisor', 'consents',
            'delivery', 'creator', 'supervisor',
            'documentsForClient',
        ]);

        if (Auth::user()->can('viewAudit', $send)) {
            $send->load('auditLogs.user');
        }

        return view('Backend.Send.show', [
            'record' => $send,
            'clientDocuments' => $send->documentsForClient,
            'titoloPagina' => 'SEND — '.$send->request_number,
            'controller' => self::class,
            'documentCategories' => SendDocumentCategory::cases(),
            'audit' => $this->audit,
            'missing' => $this->checklist->missingRequired($send),
        ]);
    }

    public function edit(SendRequest $send)
    {
        $user = Auth::user();
        if ($this->sendPolicy->isSupervisorOnly($user)) {
            return redirect()->action([self::class, 'show'], $send)
                ->with('error', 'Il supervisore gestisce la pratica dal dettaglio, non dalla modifica dati.');
        }

        $this->authorize('update', $send);
        $send->load(['subjects', 'checklistItems', 'consents', 'documents']);

        return view('Backend.Send.form-operator', [
            'record' => $send,
            'uploadUid' => null,
            'applicantTypes' => SendApplicantType::cases(),
            'priorities' => SendPriority::cases(),
            'checklistLabels' => SendChecklistService::LABELS,
            'supervisors' => $this->assignment->eligibleSupervisors(),
            'documentCategories' => SendDocumentCategory::cases(),
            'prezzoCliente' => (float) ($send->prezzo_cliente ?: $this->service->prezzoCliente()),
            'prezzoAgente' => (float) ($send->prezzo_agente ?: $this->service->prezzoAgente()),
            'portafoglioServizi' => $this->service->portafoglioServiziDi(Auth::user()),
            'titoloPagina' => 'SEND — Modifica '.$send->request_number,
            'controller' => self::class,
        ]);
    }

    public function update(StoreSendRequestRequest $request, SendRequest $send)
    {
        $this->authorize('update', $send);
        try {
            if (in_array($send->status, [SendRequestStatus::DRAFT, SendRequestStatus::INTEGRATION_REQUIRED], true)) {
                $send = $this->service->saveAndSubmit($request->user(), $request->validated(), $send);
            } else {
                $this->service->updateDraft($send, Auth::user(), $request->validated());

                return redirect()->action([self::class, 'show'], $send)->with('success', 'Pratica aggiornata.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $supervisorName = $send->supervisor?->nominativo() ?: 'supervisore';

        return redirect()->action([self::class, 'show'], $send)
            ->with('success', 'Pratica inviata al supervisore '.$supervisorName.'. Codice '.$send->request_number.'.');
    }

    public function submit(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('submit', $send);
        try {
            $this->service->submit($send, Auth::user(), $request->integer('supervisor_id') ?: null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->action([self::class, 'show'], $send)->with('success', 'Pratica inviata al supervisore.');
    }

    public function takeCharge(SendRequest $send)
    {
        $this->authorize('takeCharge', $send);
        try {
            $this->service->takeCharge($send, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica presa in carico.');
    }

    public function claim(SendRequest $send)
    {
        $this->authorize('claim', $send);
        try {
            $this->service->claim($send, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica assegnata a te.');
    }

    public function startProcessing(SendRequest $send)
    {
        $this->authorize('startProcessing', $send);
        try {
            $this->service->startProcessing($send, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Lavorazione avviata.');
    }

    public function requestIntegration(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('requestIntegration', $send);
        $data = $request->validate([
            'reason' => 'required|string|max:5000',
            'category' => 'nullable|string|max:80',
            'integration_due_at' => 'nullable|date',
        ]);
        try {
            $this->service->requestIntegration(
                $send,
                Auth::user(),
                $data['reason'],
                $data['category'] ?? null,
                $data['integration_due_at'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Integrazione richiesta all\'operatore.');
    }

    public function complete(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('complete', $send);
        try {
            $this->service->complete($send, Auth::user(), $request->input('note'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica completata.');
    }

    public function reject(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('reject', $send);
        $data = $request->validate(['reason' => 'required|string|max:5000']);
        try {
            $this->service->reject($send, Auth::user(), $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica rifiutata.');
    }

    public function cancel(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('cancel', $send);
        $data = $request->validate(['reason' => 'required|string|max:5000']);
        try {
            $this->service->cancel($send, Auth::user(), $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica annullata.');
    }

    public function deliver(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('deliver', $send);
        try {
            $this->service->deliver($send, Auth::user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Consegna registrata e pratica chiusa.');
    }

    public function addNote(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('view', $send);
        $data = $request->validate([
            'note' => 'required|string|max:5000',
            'visibility' => 'nullable|in:internal,operator,citizen',
        ]);
        $visibility = $data['visibility'] ?? 'operator';
        if ($visibility === SendNoteVisibility::INTERNAL->value) {
            $this->authorize('createInternalNote', $send);
        }
        $this->service->addNote($send, Auth::user(), $data['note'], $visibility);

        return back()->with('success', 'Nota aggiunta.');
    }

    public function uploadDocument(UploadSendDocumentRequest $request, SendRequest $send)
    {
        $this->authorize('uploadOperatorDocument', $send);
        $data = $request->validated();
        $visibility = $data['visibility'] ?? 'operator';
        if ($this->sendPolicy->isSupervisorOnly($request->user())) {
            abort(403);
        }
        if (! $this->sendPolicy->canUploadOperatorCategory($request->user(), $data['category'], $visibility)) {
            abort(403, 'Categoria o visibilità non consentita.');
        }
        $this->documents->store(
            $send,
            $request->file('file'),
            $data['category'],
            Auth::user(),
            'operator'
        );

        return back()->with('success', 'Documento caricato.');
    }

    /** Upload Dropzone (crea o modifica pratica). */
    public function uploadAllegato(Request $request)
    {
        $user = Auth::user();
        if ($this->sendPolicy->isSupervisorOnly($user)) {
            abort(403);
        }

        if (! $request->file('file')) {
            return response()->json(['success' => false, 'message' => 'File non presente'], 422);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.((int) config('send.max_upload_kb', 20480))],
            'category' => ['nullable', 'string', 'max:60'],
            'upload_uid' => ['nullable', 'string', 'max:40'],
            'send_uuid' => ['nullable', 'string', 'max:40'],
        ]);

        $category = $data['category'] ?? SendDocumentCategory::ALTRO->value;
        if (! $this->sendPolicy->canUploadOperatorCategory($user, $category, 'operator')) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria non consentita per il tuo ruolo.',
            ], 422);
        }

        try {
            $send = null;
            if (! empty($data['send_uuid'])) {
                $send = SendRequest::query()->where('uuid', $data['send_uuid'])->first();
                abort_if(! $send, 404);
                $this->authorize('uploadOperatorDocument', $send);
                $doc = $this->documents->store($send, $request->file('file'), $category, Auth::user(), 'operator');
            } else {
                abort_unless($user->can('create', SendRequest::class), 403);
                $uid = trim((string) ($data['upload_uid'] ?? ''));
                abort_if($uid === '', 422, 'upload_uid mancante');
                $doc = $this->documents->storePending($uid, $request->file('file'), $category, Auth::user(), 'operator');
            }

            return response()->json([
                'success' => true,
                'id' => $doc->id,
                'filename' => $doc->stored_name,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload non valido.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caricamento allegato non riuscito. Riprovare.',
            ], 500);
        }
    }

    public function deleteAllegato(Request $request)
    {
        abort_unless(Auth::user()->can('send.documents.upload') || Auth::user()->can('admin'), 403);

        $doc = SendRequestDocument::query()->find($request->input('id'));
        abort_if(! $doc, 404, 'File non trovato');

        if ($doc->send_request_id) {
            $this->authorize('uploadOperatorDocument', $doc->request);
        } else {
            abort_unless((int) $doc->uploaded_by === (int) Auth::id() || Auth::user()->can('admin'), 403);
        }

        $this->documents->delete($doc, Auth::user());

        return response()->json(['success' => true]);
    }

    public function uploadClientDocument(UploadSendClientDocumentRequest $request, SendRequest $send)
    {
        $this->authorize('uploadClientDocument', $send);
        $wasCompleted = $send->status === SendRequestStatus::COMPLETED;

        $record = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $send) {
            $this->documents->storeClientDocument($send, $request->file('file'), Auth::user());

            return $this->service->completeAfterClientDocumentUpload($send->fresh(), Auth::user());
        });

        if (! $wasCompleted && $record->status === SendRequestStatus::COMPLETED) {
            return back()->with('success', 'Allegato SEND caricato. Pratica completata e pronta per la consegna.');
        }

        if ($wasCompleted) {
            return back()->with('success', 'Allegato SEND aggiunto.');
        }

        return back()->with('success', 'Allegato SEND caricato.');
    }

    public function downloadClientDocument(SendRequest $send)
    {
        $this->authorize('downloadClientDocument', $send);
        $document = $this->documents->latestClientDocument($send);
        abort_if(! $document, 404, 'Allegato SEND non disponibile');

        return $this->documents->downloadResponse($document);
    }

    public function downloadDocument(SendRequest $send, SendRequestDocument $document)
    {
        abort_unless($document->send_request_id === $send->id, 404);
        $this->authorize('downloadDocument', $document);

        return $this->documents->downloadResponse($document);
    }

    public function destroyDocument(SendRequest $send, SendRequestDocument $document)
    {
        abort_unless($document->send_request_id === $send->id, 404);
        $this->authorize('uploadOperatorDocument', $send);
        abort_unless(Auth::user()->can('send.documents.delete'), 403);
        $this->documents->delete($document, Auth::user());

        return back()->with('success', 'Documento eliminato.');
    }

    public function queue(Request $request)
    {
        abort_unless(Auth::user()->can('send.requests.process'), 403);
        $filters = array_merge($request->only(['status', 'priority', 'q']), [
            'assigned_supervisor_id' => Auth::id(),
        ]);
        if (empty($filters['status'])) {
            $filters['status'] = implode(',', [
                SendRequestStatus::ASSIGNED->value,
                SendRequestStatus::RESUBMITTED->value,
            ]);
        }
        $records = $this->service->list(Auth::user(), $filters, (int) $request->get('page', 1));
        $stats = $this->service->stats(Auth::user());

        return view('Backend.Send.queue', [
            'records' => $records,
            'filters' => $filters,
            'queueStats' => [
                'to_take' => $stats['supervisor']['to_take_charge'] ?? 0,
                'taken' => SendRequest::query()
                    ->where('assigned_supervisor_id', Auth::id())
                    ->where('status', SendRequestStatus::TAKEN_IN_CHARGE->value)
                    ->count(),
                'processing' => SendRequest::query()
                    ->where('assigned_supervisor_id', Auth::id())
                    ->where('status', SendRequestStatus::PROCESSING->value)
                    ->count(),
                'urgent' => $stats['supervisor']['urgent'] ?? 0,
            ],
            'titoloPagina' => 'SEND — Coda supervisore',
            'controller' => self::class,
            'audit' => $this->audit,
        ]);
    }

    public function integrations(Request $request)
    {
        $filters = array_merge($request->only(['q']), [
            'status' => SendRequestStatus::INTEGRATION_REQUIRED->value,
        ]);
        $records = $this->service->list(Auth::user(), $filters, (int) $request->get('page', 1));

        return view('Backend.Send.index', [
            'records' => $records,
            'filters' => $filters,
            'statuses' => SendRequestStatus::cases(),
            'priorities' => SendPriority::cases(),
            'applicantTypes' => SendApplicantType::cases(),
            'titoloPagina' => 'SEND — Da integrare',
            'controller' => self::class,
            'audit' => $this->audit,
        ]);
    }

    public function settings()
    {
        $this->authorize('manageSettings', SendRequest::class);
        $keys = [
            'module_enabled', 'assignment_method', 'allow_manual_assignment',
            'default_priority', 'default_supervisor_id', 'max_upload_kb', 'privacy_version',
            'prezzo_cliente', 'prezzo_agente', 'importo_fornitore',
        ];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = SendSetting::getValue($key);
        }
        if ($settings['prezzo_cliente'] === null || $settings['prezzo_cliente'] === '') {
            $settings['prezzo_cliente'] = (string) config('send.prezzo_cliente', 5);
        }
        if ($settings['prezzo_agente'] === null || $settings['prezzo_agente'] === '') {
            $settings['prezzo_agente'] = (string) config('send.prezzo_agente', 4);
        }
        if ($settings['importo_fornitore'] === null || $settings['importo_fornitore'] === '') {
            $settings['importo_fornitore'] = (string) config('send.importo_fornitore', 0);
        }

        return view('Backend.Send.settings', [
            'settings' => $settings,
            'supervisors' => $this->assignment->eligibleSupervisors(),
            'titoloPagina' => 'SEND — Impostazioni',
            'controller' => self::class,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->authorize('manageSettings', SendRequest::class);
        $data = $request->validate([
            'module_enabled' => 'nullable|in:0,1',
            'assignment_method' => 'required|in:least_open,round_robin,default_supervisor,manual',
            'allow_manual_assignment' => 'nullable|in:0,1',
            'default_priority' => 'required|in:normale,alta,urgente',
            'default_supervisor_id' => 'nullable|integer|exists:users,id',
            'max_upload_kb' => 'required|integer|min:1024|max:102400',
            'privacy_version' => 'required|string|max:40',
            'prezzo_cliente' => 'required|numeric|min:0|max:9999',
            'prezzo_agente' => 'required|numeric|min:0|max:9999',
            'importo_fornitore' => 'required|numeric|min:0|max:9999',
        ]);
        foreach ($data as $key => $value) {
            SendSetting::setValue($key, (string) $value);
        }
        $this->audit->log('settings_update', null, null, $data);

        return back()->with('success', 'Impostazioni SEND aggiornate.');
    }

    public function report(Request $request)
    {
        $this->authorize('viewReports', SendRequest::class);
        $filters = $request->only(['from', 'to', 'status']);
        if (empty($filters['from'])) {
            $filters['from'] = now()->startOfMonth()->toDateString();
        }
        if (empty($filters['to'])) {
            $filters['to'] = now()->toDateString();
        }
        $data = $this->service->reportData(Auth::user(), $filters);

        return view('Backend.Send.report', [
            'filters' => $filters,
            'byStatus' => $data['by_status'],
            'totals' => $data['totals'],
            'rows' => $data['rows'],
            'statuses' => SendRequestStatus::cases(),
            'titoloPagina' => 'SEND — Report',
            'controller' => self::class,
            'audit' => $this->audit,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewReports', SendRequest::class);
        $filters = $request->only(['from', 'to', 'status']);
        $data = $this->service->reportData(Auth::user(), $filters);
        $filename = 'send-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'codice', 'stato', 'priorita', 'tipologia', 'creata_il',
                'operatore', 'supervisore', 'prezzo_cliente', 'prezzo_agente', 'avviso', 'iun',
            ], ';');
            foreach ($data['rows'] as $row) {
                fputcsv($out, [
                    $row->request_number,
                    $row->status->value,
                    $row->priority->value,
                    $row->applicant_type->value,
                    optional($row->created_at)->format('Y-m-d H:i'),
                    $row->creator?->nominativo(),
                    $row->supervisor?->nominativo(),
                    number_format((float) $row->prezzo_cliente, 2, ',', ''),
                    number_format((float) $row->prezzo_agente, 2, ',', ''),
                    $row->send_notice_identifier,
                    $row->iun,
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(SendRequest $send)
    {
        $this->authorize('delete', $send);
        try {
            $this->service->destroyDraft($send, Auth::user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->action([self::class, 'index'])->with('success', 'Bozza eliminata.');
    }

    public function reopen(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('reopen', $send);
        $data = $request->validate(['reason' => 'nullable|string|max:5000']);
        try {
            $this->service->reopen($send, Auth::user(), $data['reason'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->action([self::class, 'edit'], $send)->with('success', 'Pratica riaperta come bozza.');
    }

    public function reassign(SendWorkflowActionRequest $request, SendRequest $send)
    {
        $this->authorize('assign', $send);
        $data = $request->validate([
            'supervisor_id' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:5000',
        ]);
        try {
            $supervisor = User::findOrFail($data['supervisor_id']);
            $this->assignment->reassign($send, $supervisor, Auth::user(), $data['reason'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pratica riassegnata.');
    }

    public function deliveryReceiptPdf(SendRequest $send)
    {
        $this->authorize('view', $send);
        abort_unless(in_array($send->status, [
            SendRequestStatus::DELIVERED,
            SendRequestStatus::CLOSED,
        ], true), 404, 'Ricevuta disponibile solo dopo la consegna.');

        $send->load(['delivery', 'creator', 'subjects', 'supervisor']);
        $pdf = Pdf::loadView('Backend.Send.pdf.delivery-receipt', [
            'record' => $send,
            'printedAt' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download('ricevuta-consegna-'.$send->request_number.'.pdf');
    }
}

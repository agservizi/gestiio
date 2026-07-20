<?php

namespace App\Http\Services;

use App\Enums\SendDocumentCategory;
use App\Models\SendRequest;
use App\Models\SendRequestDocument;
use App\Models\SendSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SendDocumentService
{
    public function __construct(
        private SensitiveFileService $files,
        private SendAuditService $audit,
    ) {
    }

    public function store(
        SendRequest $request,
        UploadedFile $file,
        string $category,
        User $uploader,
        string $visibility = 'operator'
    ): SendRequestDocument {
        $maxKb = (int) (SendSetting::getValue('max_upload_kb') ?: config('send.max_upload_kb', 20480));
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => 'File troppo grande (max '.$maxKb.' KB).']);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $allowed = config('send.allowed_extensions', []);
        if (! in_array($ext, $allowed, true)) {
            throw ValidationException::withMessages(['file' => 'Estensione non consentita.']);
        }

        $categoryEnum = SendDocumentCategory::tryFrom($category) ?? SendDocumentCategory::ALTRO;
        $stored = $this->files->store($file, config('send.folder_prefix', 'send').'/'.$request->uuid, [
            'module' => 'send',
            'send_request_id' => $request->id,
            'category' => $categoryEnum->value,
        ]);

        $doc = $request->documents()->create([
            'category' => $categoryEnum->value,
            'visibility' => $visibility,
            'disk' => config('send.disk', 'sensitive'),
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'stored_name' => $stored['filename'],
            'mime_type' => $stored['mime_type'],
            'extension' => pathinfo($stored['filename'], PATHINFO_EXTENSION),
            'size' => $stored['size'],
            'hash' => $stored['sha256'],
            'uploaded_by' => $uploader->id,
            'antivirus_status' => 'skipped',
        ]);

        $this->audit->log('document_upload', $request, null, [
            'document_id' => $doc->id,
            'category' => $categoryEnum->value,
        ]);

        return $doc;
    }

    /**
     * Upload in bozza (prima del salvataggio pratica), collegato via upload_uid.
     */
    public function storePending(
        string $uploadUid,
        UploadedFile $file,
        string $category,
        User $uploader,
        string $visibility = 'operator'
    ): SendRequestDocument {
        $maxKb = (int) (SendSetting::getValue('max_upload_kb') ?: config('send.max_upload_kb', 20480));
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => 'File troppo grande (max '.$maxKb.' KB).']);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $allowed = config('send.allowed_extensions', []);
        if (! in_array($ext, $allowed, true)) {
            throw ValidationException::withMessages(['file' => 'Estensione non consentita.']);
        }

        $categoryEnum = SendDocumentCategory::tryFrom($category) ?? SendDocumentCategory::ALTRO;
        $folder = config('send.folder_prefix', 'send').'/pending/'.$uploadUid;
        $stored = $this->files->store($file, $folder, [
            'module' => 'send',
            'upload_uid' => $uploadUid,
            'category' => $categoryEnum->value,
        ]);

        return SendRequestDocument::query()->create([
            'send_request_id' => null,
            'upload_uid' => $uploadUid,
            'category' => $categoryEnum->value,
            'visibility' => $visibility,
            'disk' => config('send.disk', 'sensitive'),
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'stored_name' => $stored['filename'],
            'mime_type' => $stored['mime_type'],
            'extension' => pathinfo($stored['filename'], PATHINFO_EXTENSION),
            'size' => $stored['size'],
            'hash' => $stored['sha256'],
            'uploaded_by' => $uploader->id,
            'antivirus_status' => 'skipped',
        ]);
    }

    public function attachPendingByUid(string $uploadUid, SendRequest $request): int
    {
        return SendRequestDocument::query()
            ->where('upload_uid', $uploadUid)
            ->whereNull('send_request_id')
            ->update([
                'send_request_id' => $request->id,
                'upload_uid' => null,
                'updated_at' => now(),
            ]);
    }

    public function delete(SendRequestDocument $document, User $actor): void
    {
        $request = $document->request;
        if ($request) {
            $this->audit->log('document_delete', $request, [
                'document_id' => $document->id,
                'category' => $document->category?->value ?? (string) $document->category,
            ], null);
        }

        if ($this->files->exists($document->path)) {
            // soft-delete record; file retained for audit unless hard-delete policy approved
        }

        $document->delete();
    }

    public function downloadResponse(SendRequestDocument $document)
    {
        if ($document->request) {
            $this->audit->log('document_download', $document->request, null, [
                'document_id' => $document->id,
            ]);
        }

        return $this->files->download($document->path, $document->original_name);
    }

    /** Aggiunge un allegato SEND destinato al cliente (multipli consentiti). */
    public function storeClientDocument(
        SendRequest $request,
        UploadedFile $file,
        User $uploader
    ): SendRequestDocument {
        return $this->store(
            $request,
            $file,
            SendDocumentCategory::RISULTATO->value,
            $uploader,
            'citizen_receipt'
        );
    }

    public function latestClientDocument(SendRequest $request): ?SendRequestDocument
    {
        return $request->latestClientDocument();
    }
}

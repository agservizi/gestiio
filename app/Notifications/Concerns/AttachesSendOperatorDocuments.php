<?php

namespace App\Notifications\Concerns;

use App\Http\Services\SensitiveFileService;
use App\Models\SendRequest;
use Illuminate\Notifications\Messages\MailMessage;

trait AttachesSendOperatorDocuments
{
    protected function attachOperatorDocuments(MailMessage $email, SendRequest $request): MailMessage
    {
        $request->loadMissing('documents');
        $files = app(SensitiveFileService::class);

        foreach ($request->documents as $document) {
            if ($document->visibility === 'citizen_receipt') {
                continue;
            }

            if (! $files->exists($document->path)) {
                continue;
            }

            try {
                $email->attach($files->absolutePath($document->path), [
                    'as' => $document->original_name ?: 'allegato',
                    'mime' => $document->mime_type ?: 'application/octet-stream',
                ]);
            } catch (\Throwable $e) {
                // Non bloccare l'invio se un allegato non è disponibile.
            }
        }

        return $email;
    }
}

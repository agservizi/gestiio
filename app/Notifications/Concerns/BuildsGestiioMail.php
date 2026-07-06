<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait BuildsGestiioMail
{
    protected function gestiioMail(string $subject, array $data = []): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.gestiio-notification', array_replace([
                'subject' => $subject,
                'eyebrow' => 'Gestiio',
                'title' => $subject,
                'preheader' => 'Aggiornamento da Gestiio',
                'intro' => null,
                'tone' => 'info',
                'summary' => [],
                'sections' => [],
                'cta_label' => null,
                'cta_url' => null,
                'note' => null,
                'signature' => config('mail.from.name', 'Gestiio'),
            ], $data));
    }

    protected function compactRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => is_scalar($value) ? (string) $value : $value)
            ->all();
    }
}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    public function __construct(private string $otp) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $nome = trim((string) ($notifiable->nominativo() ?? $notifiable->name ?? ''));

        return (new MailMessage)
            ->subject('Codice OTP di accesso')
            ->view('emails.gestiio-notification', [
                'subject' => 'Codice OTP di accesso',
                'kicker' => 'Sicurezza',
                'title' => $nome !== '' ? 'Ciao '.$nome.', ecco il tuo codice' : 'Ecco il tuo codice di accesso',
                'preheader' => 'Il tuo codice OTP per accedere a '.config('configurazione.tag_title'),
                'tone' => 'info',
                'intro' => 'Hai richiesto un codice OTP per accedere a '.config('configurazione.tag_title').'. Inseriscilo per completare l\'accesso.',
                'code' => $this->otp,
                'note' => 'Il codice è temporaneo e va utilizzato subito. Se non hai richiesto questo accesso, ignora questa email.',
                'signature' => config('mail.from.name', 'Gestiio'),
            ]);
    }
}

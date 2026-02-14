<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    public function __construct(private string $otp)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $nome = trim((string)($notifiable->nominativo() ?? $notifiable->name ?? ''));

        return (new MailMessage)
            ->subject('Codice OTP di accesso')
            ->greeting($nome !== '' ? 'Ciao ' . $nome : 'Ciao')
            ->line('Hai richiesto un codice OTP per accedere a ' . config('configurazione.tag_title') . '.')
            ->line('Il tuo codice OTP è: ' . $this->otp)
            ->line('Il codice è temporaneo e va utilizzato subito.')
            ->line('Se non hai richiesto questo accesso, ignora questa email.');
    }
}

<?php

namespace App\Notifications;

use App\Models\SendRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaSendIntegrationRequired extends Notification
{
    use Queueable;

    public function __construct(protected SendRequest $request)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SEND: integrazione richiesta '.$this->request->request_number)
            ->line('Il supervisore richiede un\'integrazione sulla pratica '.$this->request->request_number.'.')
            ->line('Motivazione sintetica disponibile in piattaforma (senza allegati in email).')
            ->action('Apri pratica', url('/backend/send/'.$this->request->uuid));
    }
}

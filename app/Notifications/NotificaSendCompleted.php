<?php

namespace App\Notifications;

use App\Models\SendRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaSendCompleted extends Notification
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
            ->subject('SEND: pratica completata '.$this->request->request_number)
            ->line('La pratica SEND '.$this->request->request_number.' è completata. Puoi registrare la consegna al cittadino.')
            ->action('Apri pratica', url('/backend/send/'.$this->request->uuid))
            ->line('I documenti sono disponibili solo in piattaforma, non in allegato a questa email.');
    }
}

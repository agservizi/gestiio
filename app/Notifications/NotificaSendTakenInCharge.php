<?php

namespace App\Notifications;

use App\Models\SendRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaSendTakenInCharge extends Notification
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
            ->subject('SEND: pratica presa in carico '.$this->request->request_number)
            ->line('La pratica SEND '.$this->request->request_number.' è stata presa in carico / aggiornata.')
            ->action('Apri pratica', url('/backend/send/'.$this->request->uuid));
    }
}

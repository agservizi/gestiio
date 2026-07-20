<?php

namespace App\Notifications;

use App\Models\SendRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaSendAwaitingAssignment extends Notification
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
            ->subject('SEND: pratica in attesa di supervisore '.$this->request->request_number)
            ->line('Nessun supervisore SEND disponibile per la pratica '.$this->request->request_number.'.')
            ->action('Apri pratica', url('/backend/send/'.$this->request->uuid));
    }
}

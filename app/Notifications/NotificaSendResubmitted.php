<?php

namespace App\Notifications;

use App\Models\SendRequest;
use App\Notifications\Concerns\AttachesSendOperatorDocuments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaSendResubmitted extends Notification
{
    use AttachesSendOperatorDocuments;
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
        $email = (new MailMessage)
            ->subject('SEND: pratica reinviata '.$this->request->request_number)
            ->line('L\'operatore ha reinviato la pratica SEND '.$this->request->request_number.' con documentazione aggiornata.')
            ->line('Priorità: '.$this->request->priority->label())
            ->action('Apri pratica', url('/backend/send/'.$this->request->uuid))
            ->line('In allegato i documenti caricati dall\'operatore.');

        return $this->attachOperatorDocuments($email, $this->request);
    }
}

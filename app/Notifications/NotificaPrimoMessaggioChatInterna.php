<?php

namespace App\Notifications;

use App\Http\Controllers\Backend\ChatController;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaPrimoMessaggioChatInterna extends Notification
{
    use Queueable;

    protected int $threadId;

    protected string $mittente;

    protected string $estratto;

    public function __construct(ChatMessage $messaggio)
    {
        $this->threadId = (int) $messaggio->thread_id;
        $this->mittente = $messaggio->mittente?->nominativo() ?? 'Un utente';
        $this->estratto = mb_strimwidth(trim(strip_tags($messaggio->messaggio)), 0, 140, '...');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nuovo messaggio in chat interna')
            ->line('Hai ricevuto un nuovo messaggio in chat interna da '.$this->mittente.'.')
            ->line('Messaggio: "'.$this->estratto.'"')
            ->action('Apri chat', action([ChatController::class, 'index'], ['thread' => $this->threadId]));
    }

    public function toArray($notifiable)
    {
        return [];
    }
}

<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAggiornamentoTicketAUtente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param Ticket $ticket
     */
    public function __construct(protected Ticket $ticket)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Aggiornamento al tuo ticket su ' . config('configurazione.tag_title'))
            ->line('La tua richiesta è stata aggiornata')
            ->action('Accedi', url('/login'))
            ->line('grazie per aver usato il nostro servizio');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

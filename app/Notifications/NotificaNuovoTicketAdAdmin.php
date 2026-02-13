<?php

namespace App\Notifications;

use App\Http\Controllers\Backend\TicketsController;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaNuovoTicketAdAdmin extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param Ticket $ticket
     */
    public function __construct(protected $ticket)
    {
        //
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
            ->subject('Nuovo ticket su Gestiio')
            ->line('Oggetto: ' . $this->ticket->oggetto)
            ->action('Vedi tickets', action([TicketsController::class, 'show'], $this->ticket->id));
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

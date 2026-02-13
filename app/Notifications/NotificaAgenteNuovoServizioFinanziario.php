<?php

namespace App\Notifications;

use App\Models\ServizioFinanziario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAgenteNuovoServizioFinanziario extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param ServizioFinanziario $servizioFinanziario
     */
    public function __construct(protected $servizioFinanziario)
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
            ->subject('Segnalazione inserita')
            ->line('La segnalazione per ' . $this->servizioFinanziario->tipoProdottoBlade())
            ->line('di ' . $this->servizioFinanziario->nominativo())
            ->line('è stato inserita.');
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

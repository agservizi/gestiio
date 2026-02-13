<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\ServizioFinanziario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAdminServizioFinanziario extends Notification
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
            ->subject('Nuovo servizio finanziario')
            ->line('L\'agente ' . $this->servizioFinanziario->agente->nominativo())
            ->line('ha inserito una nuova segnalazione ' . $this->servizioFinanziario->tipoProdottoBlade())
            ->line('per ' . $this->servizioFinanziario->nominativo());
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

<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\ContrattoEnergia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAdminContrattoEnergia extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param ContrattoEnergia $contratto
     */
    public function __construct(protected $contratto)
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
            ->subject('Nuovo contratto')
            ->line('L\'agente ' . $this->contratto->agente->nominativo())
            ->line('ha inserito un nuovo contratto ' . $this->contratto->gestore->nome)
            ->line('per ' . $this->contratto->nominativo());
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

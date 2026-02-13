<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaScadenzaContrattoTelefonia extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contratto;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($contrattoId)
    {
        $this->contratto = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')->with('tipoContratto')->find($contrattoId);
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
            ->subject('Promemoria di scadenza offerta ' . $this->contratto->tipoContratto->nome)
            ->line('Ti informiamo che tra 20gg scade la tua offerta ' . $this->contratto->tipoContratto->nome);
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

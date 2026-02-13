<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\ContrattoEnergia;
use App\Models\Segnalazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAdminSegnalazione extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param Segnalazione $contratto
     */
    public function __construct(protected Segnalazione $contratto)
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
            ->subject('Nuova segnalazione')
            ->line('L\'agente ' . $this->contratto->agente->nominativo())
            ->line('ha inserito una nuova segnalazione ')
            ->line('nome_azienda: ' . $this->contratto->nome_azienda)
            ->line('partita_iva: ' . $this->contratto->partita_iva)
            ->line('indirizzo: ' . $this->contratto->indirizzo)
            ->line('citta: ' . $this->contratto->citta)
            ->line('cap: ' . $this->contratto->cap)
            ->line('telefono: ' . $this->contratto->telefono)
            ->line('nome_referente: ' . $this->contratto->nome_referente)
            ->line('cognome_referente: ' . $this->contratto->cognome_referente)
            ->line('email_referente: ' . $this->contratto->email_referente)
            ->line('fatturato: ' . $this->contratto->fatturato)
            ->line('settore: ' . $this->contratto->settore)
            ->line('provincia: ' . $this->contratto->provincia)
            ->line('forma_giuridica: ' . $this->contratto->forma_giuridica);
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

<?php

namespace App\Notifications;

use App\Models\AttivazioneSim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaAttivazioneSimCambioEsitoAdAgente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param AttivazioneSim $attivazioneSim
     */
    public function __construct(protected $attivazioneSim)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Attivazione sim aggiornata')
                    ->line('L\'attivazione sim di '.$this->attivazioneSim->nominativo())
                    ->line('è stata aggiornato a: ')
                    ->line(new HtmlString('<strong>'.$this->attivazioneSim->esito->nome.'</strong>'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

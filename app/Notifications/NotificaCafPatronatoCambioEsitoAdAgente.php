<?php

namespace App\Notifications;

use App\Models\CafPatronato;
use App\Models\ContrattoTelefonia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaCafPatronatoCambioEsitoAdAgente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param CafPatronato $cafPatronato
     */
    public function __construct(protected $cafPatronato)
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
            ->subject('Pratica aggiornata')
                    ->line('La pratica di '.$this->cafPatronato->nominativo())
                    ->line('è stata aggiornato a: ')
                    ->line(new HtmlString('<strong>'.$this->cafPatronato->esito->nome.'</strong>'));
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

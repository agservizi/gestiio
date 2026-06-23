<?php

namespace App\Notifications;

use App\Models\Visura;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaVisuraCambioEsitoAdAgente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Visura  $visura
     */
    public function __construct(protected $visura)
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
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Pratica aggiornata')
            ->line('La pratica di '.$this->visura->nominativo())
            ->line('è stata aggiornato a: ')
            ->line(new HtmlString('<strong>'.$this->visura->esito->nome.'</strong>'));
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

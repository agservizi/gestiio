<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaAgenteCambioEsitoContratto extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param ContrattoTelefonia $contratto
     */
    public function __construct(protected $contratto)
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
            ->subject('Contratto aggiornato')
                    ->line('Il contratto di '.$this->contratto->nominativo())
                    ->line('è stato aggiornato a: ')
                    ->line(new HtmlString('<strong>'.$this->contratto->esito->nome.'</strong>'));
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

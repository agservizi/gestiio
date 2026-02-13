<?php

namespace App\Notifications;

use App\Models\CafPatronato;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaCafPatronatoAdAdmin extends Notification
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
        $email = (new MailMessage)
            ->subject('Richiesta ' . $this->cafPatronato->tipo->nome . ' per ' . $this->cafPatronato->nominativo())
            ->line('Nominativo cliente: ' . $this->cafPatronato->nominativo())
            ->salutation(new HtmlString('Saluti,<br>'.$this->cafPatronato->agente->nominativo()));

        return $email;
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

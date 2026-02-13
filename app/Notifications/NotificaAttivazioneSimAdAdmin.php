<?php

namespace App\Notifications;

use App\Models\AttivazioneSim;
use App\Models\CafPatronato;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaAttivazioneSimAdAdmin extends Notification
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
            ->subject('Richiesta attivazione sim per ' . $this->attivazioneSim->nominativo())
            ->line('Nominativo cliente: ' . $this->attivazioneSim->nominativo())
            ->salutation(new HtmlString('Saluti,<br>Cavaliere Carmine'));

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

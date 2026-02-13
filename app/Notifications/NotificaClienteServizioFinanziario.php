<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\Gestore;
use App\Models\ServizioFinanziario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaClienteServizioFinanziario extends Notification
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


        ->greeting('Ciao ' . $this->servizioFinanziario->nome)
        ->line('Grazie per la fiducia che ci hai dedicato,')
        //->action('Notification Action', url('/'))
        ->line('la segnalazione per ' . $this->servizioFinanziario->tipoProdottoBlade())
        ->line('è stata inserita.')
        ->line('Segui l\'avanzamento sul nostro sito')
        ->salutation(new HtmlString('Saluti,<br>' . $this->servizioFinanziario->agente->nominativo()));

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

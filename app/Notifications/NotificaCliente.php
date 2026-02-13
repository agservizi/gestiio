<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\Gestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaCliente extends Notification
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

        $gestore = Gestore::find($this->contratto->tipoContratto->gestore_id);
        $logo = $gestore->immagineLogo();
        return (new MailMessage)
            ->greeting('Ciao ' . $this->contratto->nome)
            ->line('Grazie per la fiducia che ci hai dedicato,')
            //->action('Notification Action', url('/'))
            ->line('questa è l\'offerta che hai scelto:')
            ->line(new HtmlString('<strong>' . $this->contratto->tipoContratto->nome . '</strong>'))
            ->line(new HtmlString('<img src="' . url()->to($logo) . '" style="max-width:150px; text-align: center;"/>'))
            ->salutation(new HtmlString('Saluti,<br>'.$this->contratto->agente->nominativo()));
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

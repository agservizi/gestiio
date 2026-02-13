<?php

namespace App\Notifications;

use App\Models\ContrattoTelefonia;
use App\Models\Gestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaDatiAccessoCliente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param ContrattoTelefonia $contratto
     * @param string $password
     */
    public function __construct(protected $password)
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
            ->subject('Dati accesso')
            ->greeting('Ciao ' . $notifiable->name)
            ->line('ti abbiamo creato un account sul nostro sito web')
            ->line('Puoi accedere con queste credenziali:')
            ->line('Email: ' . $notifiable->email)
            ->line('Password: ' . $this->password)
            ->action('Clicca qui per accedere', route('login', ['email' => $notifiable->email, 'password' => $this->password]))
            ->salutation(new HtmlString('Saluti,<br>Cavaliere Carmine'));
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

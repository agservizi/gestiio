<?php

namespace App\Notifications;

use App\Models\MovimentoPortafoglio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use function App\importo;

class NotificaAdminMovimentoPortafoglio extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param MovimentoPortafoglio $movimento
     */
    public function __construct(protected $movimento)
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
        $utenteMovimento = User::find($this->movimento->agente_id);

        return (new MailMessage)
            ->subject('Movimento portafoglio di ' . $utenteMovimento->nominativo())
            ->line('Descrizione movimento: ' . $this->movimento->descrizione)
            ->line('Importo:' . importo($this->movimento->importo));
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

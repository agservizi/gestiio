<?php

namespace App\Notifications;

use App\Http\Controllers\Backend\FormController;
use App\Models\MessaggioFormAssistenza;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RichiestaFormNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected $form)
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
            ->greeting('')
            ->subject('Richiesta dal form')
            ->line('Nominativo: ' . $this->form->nominativo)
            ->line('Email: ' . $this->form->email)
            ->line('Telefono: ' . $this->form->telefono)
            ->line('Messaggio: ' . $this->form->messaggio)
            ->action('Vedi richieste', url()->action([FormController::class, 'index']));
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

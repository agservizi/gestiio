<?php

namespace App\Notifications;

use App\Models\AllegatoCafPatronato;
use App\Models\CafPatronato;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaCafPatronatoACliente extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected CafPatronato $cafPatronato)
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
            ->line('Le inviamo la sua pratica ' . $this->cafPatronato->tipo->nome)
            ->subject('Invio pratica ' . $this->cafPatronato->tipo->nome)
            ->salutation($this->cafPatronato->agente->nominativo());

        foreach (AllegatoCafPatronato::where('caf_patronato_id', $this->cafPatronato->id)->where('per_cliente', 1)->get() as $file) {
            $estensione = pathinfo($file->path_filename, PATHINFO_EXTENSION);
            $email->attach(\Storage::path($file->path_filename), ['as' => ucfirst(\Str::slug($this->cafPatronato->tipo->nome)) . '.' . $estensione]);
        }

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

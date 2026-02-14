<?php

namespace App\Notifications;

use App\Models\CafPatronato;
use App\Models\Comune;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class NotificaCafPatronato extends Notification
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
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $agente = $this->cafPatronato->agente;
        if ($agente) {
            $fromName = $agente->nominativo();
        }

        $email = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject('Richiesta ' . $this->cafPatronato->tipo->nome . ' per ' . $this->cafPatronato->nominativo())
            ->line('Nominativo cliente: ' . $this->cafPatronato->nominativo())
            ->line('Codice fiscale: ' . $this->cafPatronato->nominativo())
            ->line('Indirizzo: ' . $this->cafPatronato->indirizzo)
            ->line('Città: ' . Comune::find($this->cafPatronato->citta)?->comuneConTarga())
            ->line('Cap: ' . $this->cafPatronato->cap)
            ->line('Email: ' . $this->cafPatronato->email)
            ->line('Cellulare: ' . $this->cafPatronato->cellulare)
            ->line('Note: ' . $this->cafPatronato->note)
            ->salutation(new HtmlString('Saluti,<br>' . ($agente?->nominativo() ?? config('mail.from.name'))));

        if ($agente?->email) {
            $email->replyTo($agente->email, $agente->nominativo());
        }

        if ($this->cafPatronato->allegati) {
            foreach ($this->cafPatronato->allegati as $allegato) {
                if ($allegato->path_filename && Storage::exists($allegato->path_filename)) {
                    $email->attach(Storage::path($allegato->path_filename));
                    continue;
                }

                if ($allegato->file_contenuto_base64) {
                    $contenuto = base64_decode($allegato->file_contenuto_base64, true);
                    if ($contenuto !== false) {
                        $email->attachData($contenuto, $allegato->filename_originale ?: 'allegato', [
                            'mime' => $allegato->mime_type ?: 'application/octet-stream',
                        ]);
                    }
                }
            }
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

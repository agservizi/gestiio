<?php

namespace App\Notifications;

use App\Models\ContrattoEnergia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaAdminDocumentiContrattoEnergiaRicevuti extends Notification
{
    use Queueable;

    public function __construct(
        protected ContrattoEnergia $contratto,
        protected int $allegatiCount
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Documenti cliente ricevuti - Contratto energia #' . $this->contratto->id)
            ->greeting('Documenti cliente ricevuti')
            ->line('Il cliente ha completato l\'upload documenti da magic-link.')
            ->line('Contratto: #' . $this->contratto->id)
            ->line('Cliente: ' . $this->contratto->nominativo())
            ->line('Gestore: ' . ($this->contratto->gestore?->nome ?? '-'))
            ->line('Email cliente: ' . $this->contratto->email)
            ->line('Allegati ricevuti: ' . $this->allegatiCount)
            ->line('Link attivazione gestore: ' . ($this->contratto->link_attivazione_gestore ?: '-'))
            ->action('Apri contratto in backend', action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'show'], [$this->contratto->id]));
    }
}

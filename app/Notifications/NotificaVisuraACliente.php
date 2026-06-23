<?php

namespace App\Notifications;

use App\Models\AllegatoServizio;
use App\Models\Visura;
use App\Notifications\Concerns\UsesPersonalizedMailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class NotificaVisuraACliente extends Notification
{
    use Queueable;
    use UsesPersonalizedMailSender;

    public function __construct(
        protected Visura $visura,
        protected AllegatoServizio $allegato
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $agente = $this->visura->agente;
        $tipoNome = trim((string) optional($this->visura->tipo)->nome);
        if ($tipoNome === '') {
            $tipoNome = 'visura';
        }

        $email = (new MailMessage)
            ->subject('Invio visura '.$tipoNome)
            ->line('Le inviamo la visura richiesta: '.$tipoNome.'.')
            ->line('In allegato trova il documento PDF.')
            ->salutation($agente?->nominativo() ?: config('mail.from.name'));

        $email = $this->applyPersonalizedSender($email, $agente);

        $nomeAllegato = trim((string) $this->allegato->filename_originale);
        if ($nomeAllegato === '') {
            $nomeAllegato = 'visura_'.$this->visura->id.'.pdf';
        }

        if ($this->allegato->path_filename && Storage::exists($this->allegato->path_filename)) {
            $email->attach(Storage::path($this->allegato->path_filename), ['as' => $nomeAllegato]);

            return $email;
        }

        if ($this->allegato->file_contenuto_base64) {
            $contenuto = base64_decode($this->allegato->file_contenuto_base64, true);
            if ($contenuto !== false) {
                $email->attachData($contenuto, $nomeAllegato, [
                    'mime' => $this->allegato->mime_type ?: 'application/pdf',
                ]);
            }
        }

        return $email;
    }
}

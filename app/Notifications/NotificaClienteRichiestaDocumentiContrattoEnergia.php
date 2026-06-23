<?php

namespace App\Notifications;

use App\Models\ContrattoEnergia;
use App\Notifications\Concerns\UsesPersonalizedMailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaClienteRichiestaDocumentiContrattoEnergia extends Notification
{
    use Queueable;
    use UsesPersonalizedMailSender;

    public function __construct(
        protected ContrattoEnergia $contratto,
        protected string $magicUrl,
        protected string $templateUrl,
        protected string $expiresAtLabel
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $nome = trim((string) ($this->contratto->nome ?: $this->contratto->denominazione));

        $email = (new MailMessage)
            ->greeting('Ciao '.($nome !== '' ? $nome : 'Cliente'))
            ->line('Per completare la tua pratica energia servono documenti aggiuntivi.')
            ->line(new HtmlString('<strong>Documento richiesto: Voltura/Subentro firmato in tutte le parti obbligatorie.</strong>'))
            ->line('Nel form ti verrà richiesto anche il link di attivazione inviato dal gestore (ENEL via email, A2A via email/SMS).')
            ->action('Scarica modulo da firmare', $this->templateUrl)
            ->line('Accedi con il link qui sotto (senza login) e carica il documento richiesto.')
            ->action('Carica documento firmato', $this->magicUrl)
            ->line('Il link scade il: '.$this->expiresAtLabel)
            ->salutation(new HtmlString('Saluti,<br>'.($this->contratto->agente?->nominativo() ?? config('mail.from.name'))));

        return $this->applyPersonalizedSender($email, $this->contratto->agente);
    }
}

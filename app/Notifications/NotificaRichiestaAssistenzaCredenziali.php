<?php

namespace App\Notifications;

use App\Models\RichiestaAssistenza;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaRichiestaAssistenzaCredenziali extends Notification
{
    use Queueable;

    public function __construct(
        protected RichiestaAssistenza $richiesta,
        protected string $pdfContent,
        protected string $pdfFileName
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $cliente = $this->richiesta->cliente;
        $prodotto = (string) optional($this->richiesta->prodotto)->nome;
        $prodotto = $prodotto !== '' ? $prodotto : 'Richiesta assistenza';

        $email = (new MailMessage)
            ->subject('Credenziali '.$prodotto)
            ->greeting('Ciao '.trim((string) optional($cliente)->nome))
            ->line('In allegato trovi il PDF con le credenziali della tua richiesta assistenza.')
            ->line('Prodotto: '.$prodotto);

        if ((string) $this->richiesta->nome_utente !== '') {
            $email->line('Nome utente: '.$this->richiesta->nome_utente);
        }
        if ((string) $this->richiesta->password !== '') {
            $email->line('Password: '.$this->richiesta->password);
        }
        if ((string) $this->richiesta->pin !== '') {
            $email->line('PIN: '.$this->richiesta->pin);
        }

        return $email
            ->attachData($this->pdfContent, $this->pdfFileName, ['mime' => 'application/pdf'])
            ->salutation(config('mail.from.name'));
    }
}

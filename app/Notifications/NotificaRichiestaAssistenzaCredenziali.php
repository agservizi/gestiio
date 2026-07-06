<?php

namespace App\Notifications;

use App\Models\RichiestaAssistenza;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaRichiestaAssistenzaCredenziali extends Notification
{
    use Queueable;
    use BuildsGestiioMail;

    public function __construct(
        protected RichiestaAssistenza $richiesta,
        protected string $pdfContent,
        protected string $pdfFilename
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->richiesta->loadMissing('cliente', 'prodotto');
        $cliente = trim((string) optional($this->richiesta->cliente)->nominativo()) ?: 'Cliente';
        $prodotto = optional($this->richiesta->prodotto)->nome ?? 'Assistenza';

        return $this->gestiioMail('Documenti e credenziali per la tua richiesta', [
            'eyebrow' => 'Assistenza Gestiio',
            'title' => 'La tua richiesta è pronta',
            'preheader' => 'Trovi il documento allegato e le informazioni per procedere.',
            'intro' => 'Ciao '.$cliente.', abbiamo preparato il documento collegato alla tua richiesta. Lo trovi in allegato a questa email.',
            'summary' => [
                'Richiesta' => '#'.$this->richiesta->id,
                'Servizio' => $prodotto,
                'Documento' => $this->pdfFilename,
            ],
            'sections' => [
                [
                    'title' => 'Cosa fare ora',
                    'items' => [
                        '1' => 'Scarica il PDF allegato.',
                        '2' => 'Controlla che i dati riportati siano corretti.',
                        '3' => 'Conserva questa email: ti servirà come riferimento.',
                    ],
                ],
            ],
            'note' => 'Se qualcosa non torna, rispondi alla mail o contatta il tuo referente.',
            'signature' => config('mail.from.name'),
        ])->attachData($this->pdfContent, $this->pdfFilename, [
            'mime' => 'application/pdf',
        ]);
    }
}

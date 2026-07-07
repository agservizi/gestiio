<?php

namespace App\Notifications;

use App\Models\OrdineEbike;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEbikePagamentoDaVerificare extends Notification
{
    use BuildsGestiioMail;
    use Queueable;

    public function __construct(protected OrdineEbike $ordine)
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $nominativo = $this->ordine->agente?->nominativo() ?? 'Agente #'.$this->ordine->agente_id;
        $subject = 'Bonifico da verificare - ordine ebike di '.$nominativo;

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Ebike B2B',
            'title' => 'Ricevuta bonifico caricata',
            'preheader' => $subject,
            'tone' => 'warning',
            'intro' => 'L\'agente ha caricato la ricevuta del bonifico istantaneo. Verifica l\'accredito e conferma il pagamento per far partire i 10 giorni di spedizione.',
            'summary' => [
                'Agente' => $nominativo,
                'Totale' => importo((float) $this->ordine->totale, true),
                'CRO' => $this->ordine->cro_bonifico,
            ],
            'cta_label' => 'Verifica ordine',
            'cta_url' => url('/backend/ebike/ordini/'.$this->ordine->id),
            'signature' => config('mail.from.name'),
        ]);
    }

    public function toArray($notifiable)
    {
        return [];
    }
}

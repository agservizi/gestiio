<?php

namespace App\Notifications;

use App\Models\OrdineEbike;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEbikeNuovoOrdine extends Notification
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
        $subject = 'Nuovo ordine ebike da '.$nominativo;

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Ebike B2B',
            'title' => 'Nuovo ordine ricevuto',
            'preheader' => $subject,
            'intro' => 'Un agente ha creato un nuovo ordine ebike. In attesa del bonifico istantaneo.',
            'summary' => [
                'Agente' => $nominativo,
                'Totale' => importo((float) $this->ordine->totale, true),
                'Stato' => $this->ordine->stato->testo(),
            ],
            'cta_label' => 'Apri ordine',
            'cta_url' => url('/backend/ebike/ordini/'.$this->ordine->id),
            'signature' => config('mail.from.name'),
        ]);
    }

    public function toArray($notifiable)
    {
        return [];
    }
}

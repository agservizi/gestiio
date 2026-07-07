<?php

namespace App\Notifications;

use App\Models\OrdineEbike;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEbikeSlaSuperato extends Notification
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
        $subject = 'SLA spedizione superato - ordine ebike #'.$this->ordine->id;
        $scadenza = $this->ordine->scadenza_spedizione?->format('d/m/Y');

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Ebike B2B',
            'title' => 'Spedizione in ritardo',
            'preheader' => $subject,
            'tone' => 'critical',
            'intro' => 'Un ordine ebike ha superato i '.OrdineEbike::GIORNI_SLA_SPEDIZIONE.' giorni previsti dalla conferma del pagamento senza essere stato spedito.',
            'summary' => [
                'Ordine' => '#'.$this->ordine->id,
                'Agente' => $nominativo,
                'Totale' => importo((float) $this->ordine->totale, true),
                'Scadenza SLA' => $scadenza,
            ],
            'cta_label' => 'Gestisci spedizione',
            'cta_url' => url('/backend/ebike/ordini/'.$this->ordine->id),
            'signature' => config('mail.from.name'),
        ]);
    }

    public function toArray($notifiable)
    {
        return [];
    }
}

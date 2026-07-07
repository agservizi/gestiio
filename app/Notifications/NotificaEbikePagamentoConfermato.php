<?php

namespace App\Notifications;

use App\Models\OrdineEbike;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEbikePagamentoConfermato extends Notification
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
        $subject = 'Pagamento confermato - ordine ebike #'.$this->ordine->id;
        $scadenza = $this->ordine->scadenza_spedizione?->format('d/m/Y');

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Ebike B2B',
            'title' => 'Il tuo pagamento è stato confermato',
            'preheader' => $subject,
            'tone' => 'success',
            'intro' => 'Abbiamo confermato il bonifico per il tuo ordine ebike. Verrà spedito entro '.OrdineEbike::GIORNI_SLA_SPEDIZIONE.' giorni, con tracking.',
            'summary' => [
                'Ordine' => '#'.$this->ordine->id,
                'Totale' => importo((float) $this->ordine->totale, true),
                'Spedizione entro' => $scadenza,
            ],
            'cta_label' => 'Vedi ordine',
            'cta_url' => url('/backend/ebike/ordini/'.$this->ordine->id),
            'signature' => config('mail.from.name'),
        ]);
    }

    public function toArray($notifiable)
    {
        return [];
    }
}

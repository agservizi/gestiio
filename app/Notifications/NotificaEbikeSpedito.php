<?php

namespace App\Notifications;

use App\Models\OrdineEbike;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEbikeSpedito extends Notification
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
        $subject = 'Ordine ebike #'.$this->ordine->id.' spedito';

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Ebike B2B',
            'title' => 'Il tuo ordine è stato spedito',
            'preheader' => $subject,
            'tone' => 'success',
            'intro' => 'Il tuo ordine ebike è partito. Ecco i dati per tracciare la spedizione.',
            'summary' => [
                'Ordine' => '#'.$this->ordine->id,
                'Totale' => importo((float) $this->ordine->totale, true),
                'Corriere' => $this->ordine->corriere,
                'Numero tracking' => $this->ordine->tracking_number,
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

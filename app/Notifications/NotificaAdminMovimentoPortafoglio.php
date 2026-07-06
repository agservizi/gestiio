<?php

namespace App\Notifications;

use App\Models\MovimentoPortafoglio;
use App\Models\User;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaAdminMovimentoPortafoglio extends Notification
{
    use Queueable;
    use BuildsGestiioMail;

    /**
     * Create a new notification instance.
     *
     * @param  MovimentoPortafoglio  $movimento
     */
    public function __construct(protected $movimento)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $utenteMovimento = User::find($this->movimento->agente_id);
        $agente = $utenteMovimento?->nominativo() ?? 'Agente non disponibile';
        $subject = 'Movimento portafoglio di '.$agente;

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Portafoglio',
            'title' => 'Nuovo movimento portafoglio',
            'preheader' => 'Movimento registrato per '.$agente,
            'intro' => 'È stato registrato un movimento sul portafoglio agente. Qui trovi i dati essenziali per controllarlo rapidamente.',
            'summary' => [
                'Agente' => $agente,
                'Importo' => importo($this->movimento->importo),
            ],
            'sections' => [
                [
                    'title' => 'Dettaglio movimento',
                    'items' => $this->compactRows([
                        'Descrizione' => $this->movimento->descrizione,
                    ]),
                ],
            ],
            'cta_label' => 'Apri il backend',
            'cta_url' => url('/backend'),
            'signature' => config('mail.from.name'),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

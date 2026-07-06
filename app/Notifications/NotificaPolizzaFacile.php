<?php

namespace App\Notifications;

use App\Models\ServizioFinanziario;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaPolizzaFacile extends Notification
{
    use Queueable;
    use BuildsGestiioMail;

    /**
     * Create a new notification instance.
     *
     * @param  ServizioFinanziario  $servizioFinanziario
     */
    public function __construct(protected $servizioFinanziario)
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
        $prodottoBlade = $this->servizioFinanziario->tipoProdottoBlade();
        $prodotto = $this->servizioFinanziario->prodotto()->first();
        $items = [];

        switch ($prodottoBlade) {
            case 'PolizzaFacile':
                $items = [
                    'Targa' => $prodotto->targa,
                    'Data di nascita' => $prodotto->data_di_nascita?->format('d/m/Y'),
                    'Importo attuale polizza' => importo($prodotto->importo_attuale),
                ];
                break;

        }

        return $this->gestiioMail('Segnalazione PolizzaFacile', [
            'eyebrow' => 'PolizzaFacile',
            'title' => 'Nuova richiesta assicurativa',
            'preheader' => 'Cliente: '.$this->servizioFinanziario->nominativo(),
            'intro' => 'È arrivata una nuova richiesta PolizzaFacile. Di seguito trovi i dati utili per procedere con la valutazione.',
            'summary' => [
                'Prodotto' => $prodottoBlade,
                'Cliente' => $this->servizioFinanziario->nominativo(),
            ],
            'sections' => [
                [
                    'title' => 'Dati polizza',
                    'items' => $this->compactRows($items),
                ],
            ],
            'signature' => 'Cavaliere Carmine',
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

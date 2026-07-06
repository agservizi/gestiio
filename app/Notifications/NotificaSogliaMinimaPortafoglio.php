<?php

namespace App\Notifications;

use App\Enums\TipiPortafoglioEnum;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaSogliaMinimaPortafoglio extends Notification
{
    use BuildsGestiioMail;
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected TipiPortafoglioEnum $tipo,
        protected float $saldo,
        protected float $soglia,
        protected string $nominativoAgente,
        protected bool $perAgente = false,
    ) {}

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
        $subject = 'Portafoglio '.$this->tipo->testo().' sotto la soglia minima';

        $intro = $this->perAgente
            ? 'Il tuo portafoglio '.$this->tipo->testo().' è sceso sotto la soglia minima di '.importo($this->soglia, true).'. Ricaricalo per continuare a lavorare senza interruzioni.'
            : 'Il portafoglio '.$this->tipo->testo().' di '.$this->nominativoAgente.' è sceso sotto la soglia minima di '.importo($this->soglia, true).' e va ricaricato al più presto.';

        return $this->gestiioMail($subject, [
            'eyebrow' => 'Portafoglio',
            'title' => 'Credito in esaurimento',
            'preheader' => $subject,
            'tone' => 'warning',
            'intro' => $intro,
            'summary' => [
                'Agente' => $this->nominativoAgente,
                'Portafoglio' => $this->tipo->testo(),
                'Saldo attuale' => importo($this->saldo, true),
                'Soglia minima' => importo($this->soglia, true),
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
        return [];
    }
}

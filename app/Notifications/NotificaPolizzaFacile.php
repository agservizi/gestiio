<?php

namespace App\Notifications;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\ServizioFinanziario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use function App\importo;

class NotificaPolizzaFacile extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param ServizioFinanziario $servizioFinanziario
     */
    public function __construct(protected $servizioFinanziario)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $email = (new MailMessage)
            ->subject('SEGNALAZIONE CAVALIERE CARMINE GESTIIO')
            ->line('Prodotto: ' . $this->servizioFinanziario->tipoProdottoBlade())
            ->line('Nominativo cliente: ' . $this->servizioFinanziario->nominativo())
            ->salutation(new HtmlString('Saluti,<br>Cavaliere Carmine'));

        $prodotto = $this->servizioFinanziario->prodotto()->first();


        switch ($this->servizioFinanziario->tipoProdottoBlade()) {
            case 'PolizzaFacile':
                $email->line('Targa: ' . $prodotto->targa);
                $email->line('Data di nascita: ' . $prodotto->data_di_nascita->format('d/m/Y'));
                $email->line('Importo attuale polizza: ' . importo($prodotto->importo_attuale));
                break;

        }

        return $email;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

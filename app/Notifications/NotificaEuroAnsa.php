<?php

namespace App\Notifications;

use App\Models\Comune;
use App\Models\ServizioFinanziario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use function App\importo;

class NotificaEuroAnsa extends Notification
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
            case 'Polizza':
                $email->line('Targa: ' . $prodotto->targa);
                $email->line('Data di nascita: ' . $prodotto->data_di_nascita->format('d/m/Y'));
                break;

            case 'Mutuo':
                $email->line('Finalità: ' . $prodotto->finalita);
                $email->line('Tipo di tasso: ' . $prodotto->tipo_di_tasso);
                $email->line('Valore immobile: ' . $prodotto->valore_immobile);
                $email->line('Importo del mutuo: ' . $prodotto->importo_del_mutuo);
                $email->line('Durata: ' . $prodotto->durata . ' anni');
                $email->line('Data di nascita: ' . $prodotto->data_di_nascita->format('d/m/Y'));
                $email->line('Posizione lavorativa: ' . $prodotto->posizione_lavorativa);
                $email->line('Reddito richiedenti: ' . $prodotto->reddito_richiedenti);
                $email->line('Comune domicilio: ' . Comune::find($prodotto->comune_domicilio)?->comuneConTarga());
                $email->line('Comune immobile: ' . Comune::find($prodotto->comune_immobile)?->comuneConTarga());
                $email->line('Stato ricerca immobile: ' . $prodotto->stato_ricerca_immobile);
                break;

            case 'Prestito':
                $email->line('importo prestito: ' . $prodotto->importo_prestito);
                $email->line('Durata prestito: ' . $prodotto->durata_prestito . ' mesi');
                $email->line('Stato civile: ' . $prodotto->stato_civile);
                $email->line('Immobile residenza: ' . $prodotto->immobile_residenza);
                $email->line('Telefono fisso: ' . $prodotto->telefono_fisso);
                $email->line('Prestiti in corso: ' . ($prodotto->prestiti_in_corso ? 'Si' : 'No'));
                $email->line('Prestiti in passato: ' . ($prodotto->prestiti_in_passato ? 'Si' : 'No'));
                $email->line('Motivazione prestito: ' . $prodotto->motivazione_prestito);
                $email->line('Componenti famiglia: ' . $prodotto->componenti_famiglia);
                $email->line('Componenti famiglia con reddito: ' . $prodotto->componenti_famiglia_con_reddito);
                $email->line('Lavoro: ' . $prodotto->lavoro);
                $email->line('Datore lavoro intestazione: ' . $prodotto->datore_lavoro_intestazione);
                $email->line('Mesi anzianità servizio: ' . $prodotto->mesi_anzianita_servizio);
                $email->line('Anni anzianità servizio: ' . $prodotto->anni_anzianita_servizio);
                $email->line('Indirizzo lavoro: ' . $prodotto->indirizzo_lavoro);
                $email->line('Citta lavoro: ' . Comune::find($prodotto->citta_lavoro)?->comuneConTarga());
                $email->line('Telefono lavoro: ' . $prodotto->telefono_lavoro);
                $email->line('Titolo studio: ' . $prodotto->titolo_studio);
                $email->line('Reddito mensile: ' . importo($prodotto->reddito_mensile));
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

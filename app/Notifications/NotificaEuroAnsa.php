<?php

namespace App\Notifications;

use App\Models\Comune;
use App\Models\ServizioFinanziario;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function importo;

class NotificaEuroAnsa extends Notification
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
            case 'Polizza':
                $items = [
                    'Targa' => $prodotto->targa,
                    'Data di nascita' => $prodotto->data_di_nascita?->format('d/m/Y'),
                ];
                break;

            case 'Mutuo':
                $items = [
                    'Finalità' => $prodotto->finalita,
                    'Tipo di tasso' => $prodotto->tipo_di_tasso,
                    'Valore immobile' => $prodotto->valore_immobile,
                    'Importo del mutuo' => $prodotto->importo_del_mutuo,
                    'Durata' => $prodotto->durata.' anni',
                    'Data di nascita' => $prodotto->data_di_nascita?->format('d/m/Y'),
                    'Posizione lavorativa' => $prodotto->posizione_lavorativa,
                    'Reddito richiedenti' => $prodotto->reddito_richiedenti,
                    'Comune domicilio' => Comune::find($prodotto->comune_domicilio)?->comuneConTarga(),
                    'Comune immobile' => Comune::find($prodotto->comune_immobile)?->comuneConTarga(),
                    'Stato ricerca immobile' => $prodotto->stato_ricerca_immobile,
                ];
                break;

            case 'Prestito':
                $items = [
                    'Importo prestito' => $prodotto->importo_prestito,
                    'Durata prestito' => $prodotto->durata_prestito.' mesi',
                    'Stato civile' => $prodotto->stato_civile,
                    'Immobile residenza' => $prodotto->immobile_residenza,
                    'Telefono fisso' => $prodotto->telefono_fisso,
                    'Prestiti in corso' => $prodotto->prestiti_in_corso ? 'Si' : 'No',
                    'Prestiti in passato' => $prodotto->prestiti_in_passato ? 'Si' : 'No',
                    'Motivazione prestito' => $prodotto->motivazione_prestito,
                    'Componenti famiglia' => $prodotto->componenti_famiglia,
                    'Componenti famiglia con reddito' => $prodotto->componenti_famiglia_con_reddito,
                    'Lavoro' => $prodotto->lavoro,
                    'Datore lavoro' => $prodotto->datore_lavoro_intestazione,
                    'Anzianità servizio' => trim($prodotto->anni_anzianita_servizio.' anni '.$prodotto->mesi_anzianita_servizio.' mesi'),
                    'Indirizzo lavoro' => $prodotto->indirizzo_lavoro,
                    'Città lavoro' => Comune::find($prodotto->citta_lavoro)?->comuneConTarga(),
                    'Telefono lavoro' => $prodotto->telefono_lavoro,
                    'Titolo studio' => $prodotto->titolo_studio,
                    'Reddito mensile' => importo($prodotto->reddito_mensile),
                ];
                break;

        }

        return $this->gestiioMail('Segnalazione Gestiio - '.$prodottoBlade, [
            'eyebrow' => 'Servizi finanziari',
            'title' => 'Nuova segnalazione '.$prodottoBlade,
            'preheader' => 'Cliente: '.$this->servizioFinanziario->nominativo(),
            'intro' => 'È arrivata una nuova richiesta da valutare. I dati principali sono raccolti qui sotto per una prima lettura rapida.',
            'summary' => [
                'Prodotto' => $prodottoBlade,
                'Cliente' => $this->servizioFinanziario->nominativo(),
            ],
            'sections' => [
                [
                    'title' => 'Dati richiesta',
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

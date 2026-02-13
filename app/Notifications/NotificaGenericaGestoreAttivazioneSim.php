<?php

namespace App\Notifications;

use App\Models\AttivazioneSim;
use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use function App\importo;

class NotificaGenericaGestoreAttivazioneSim extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param AttivazioneSim $attivazioneSim
     */
    public function __construct(protected $attivazioneSim)
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
            ->subject($this->attivazioneSim->gestore->titolo_notifica_a_gestore . ' - ' . $this->attivazioneSim->nome . ' per ' . $this->attivazioneSim->nominativo());


        if ($this->attivazioneSim->gestore->testo_notifica_a_gestore) {
            $email->line($this->attivazioneSim->gestore->testo_notifica_a_gestore);
        }


        if ($this->attivazioneSim->gestore->includi_dati_contratto) {
            $email->line(new HtmlString('<strong>Dati contratto</strong>'));
            $email->line('Cognome: ' . $this->attivazioneSim->cognome);
            $email->line('Nome: ' . $this->attivazioneSim->nome);
            $email->line('Codice fiscale: ' . $this->attivazioneSim->codice_fiscale);
            $email->line('Email: ' . $this->attivazioneSim->email);
            $email->line('Cellulare: ' . $this->attivazioneSim->cellulare);
            $email->line('Indirizzo: ' . $this->attivazioneSim->indirizzo);
            $email->line('Città: ' . Comune::find($this->attivazioneSim->citta)?->comuneConTarga());
            $email->line('Cap: ' . $this->attivazioneSim->cap);
            $email->line('Tipo documento: ' . ($this->attivazioneSim->tipo_documento ? ContrattoTelefonia::TIPI_DOCUMENTO[$this->attivazioneSim->tipo_documento] : ''));
            $email->line('Numero documento: ' . $this->attivazioneSim->numero_documento);
            $email->line('Data scadenza: ' . $this->attivazioneSim->data_scadenza?->format('d/m/Y'));
            $email->line('Offerta: ' . $this->attivazioneSim->offerta->nome);
            $email->line('Seriale sim nuova: ' . $this->attivazioneSim->codice_sim);
            $email->line('Mnp: ' . $this->attivazioneSim->mnp);
            $email->line('Numero da portare: ' . $this->attivazioneSim->numero_da_portare);

            $email->line(' ');
        }
        $conteggio = count($this->attivazioneSim->allegati);
        if ($conteggio) {
            $email->line($conteggio . ' Documenti in allegato');
        }

        $email->salutation(new HtmlString('Saluti,<br>Cavaliere Carmine'));

        foreach ($this->attivazioneSim->allegati as $allegato) {
            $email->attach(\Storage::path($allegato->path_filename));
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

<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesPersonalizedMailSender;
use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use function App\importo;
use function App\siNo;

class NotificaGenericaGestore extends Notification
{
    use Queueable;
    use UsesPersonalizedMailSender;

    /**
     * Create a new notification instance.
     *
     * @param ContrattoTelefonia $contratto
     */
    public function __construct(protected $contratto)
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
            ->subject($this->contratto->tipoContratto->gestore->titolo_notifica_a_gestore . ' - ' . $this->contratto->tipoContratto->nome . ' per ' . $this->contratto->nominativo());


        if ($this->contratto->tipoContratto->gestore->testo_notifica_a_gestore) {
            $email->line($this->contratto->tipoContratto->gestore->testo_notifica_a_gestore);
        }


        if ($this->contratto->tipoContratto->gestore->includi_dati_contratto) {
            $email->line(new HtmlString('<strong>Dati contratto</strong>'));
            $email->line('Cognome: ' . $this->contratto->cognome);
            $email->line('Nome: ' . $this->contratto->nome);
            $email->line('Codice fiscale: ' . $this->contratto->codice_fiscale);
            $email->line('Email: ' . $this->contratto->email);
            $email->line('Telefono: ' . $this->contratto->telefono);
            $email->line('Indirizzo: ' . $this->contratto->indirizzo);
            $email->line('Città: ' . Comune::find($this->contratto->citta)?->comuneConTarga());
            $email->line('Cap: ' . $this->contratto->cap);
            $email->line('Tipo documento: ' . $this->contratto->tipo_documento ? ContrattoTelefonia::TIPI_DOCUMENTO[$this->contratto->tipo_documento] : '');
            $email->line('Numero documento: ' . $this->contratto->numero_documento);
            $email->line('Rilasciato da: ' . $this->contratto->rilasciato_da);
            $email->line('Data rilascio: ' . $this->contratto->data_rilascio?->format('d/m/Y'));
            $email->line('Data scadenza: ' . $this->contratto->data_scadenza?->format('d/m/Y'));
            $email->line(' ');

            if ($this->contratto->prodotto_type == 'App\Models\ProdottoTimWifi') {
                $email->line('Pagamento bollettino postale: ' . siNo($this->contratto->prodotto->pagamento_bollettino));
            }

        }

        $conteggio = count($this->contratto->allegati);
        if ($conteggio) {
            $email->line($conteggio . ' Documenti in allegato');
        }

        $email->salutation(new HtmlString('Saluti,<br>' . ($this->contratto->agente?->nominativo() ?? config('mail.from.name'))));

        foreach ($this->contratto->allegati as $allegato) {
            $email->attach(Storage::path($allegato->path_filename));
        }

        if ($this->contratto->tipoContratto->pda) {
            //Allega PDA
            $classe = 'App\Http\MieClassi\Pdf' . $this->contratto->tipoContratto->pda;
            $pdf = new $classe();
            $pdf->generaPdf($this->contratto);
            $email->attachData($pdf->render('S'), $pdf->getNomeDocumento(), [
                'mime' => 'application/pdf',
            ]);
        }

        return $this->applyPersonalizedSender($email, $this->contratto->agente);
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

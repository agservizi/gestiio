<?php

namespace App\Notifications;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Notifications\Concerns\BuildsGestiioMail;
use App\Notifications\Concerns\UsesPersonalizedMailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

use function siNo;

class NotificaGenericaGestore extends Notification
{
    use Queueable;
    use BuildsGestiioMail;
    use UsesPersonalizedMailSender;

    /**
     * Create a new notification instance.
     *
     * @param  ContrattoTelefonia  $contratto
     */
    public function __construct(protected $contratto)
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
        $subject = $this->contratto->tipoContratto->gestore->titolo_notifica_a_gestore.' - '.$this->contratto->tipoContratto->nome.' per '.$this->contratto->nominativo();
        $sections = [];

        if ($this->contratto->tipoContratto->gestore->testo_notifica_a_gestore) {
            $sections[] = [
                'title' => 'Messaggio per il gestore',
                'intro' => $this->contratto->tipoContratto->gestore->testo_notifica_a_gestore,
            ];
        }

        if ($this->contratto->tipoContratto->gestore->includi_dati_contratto) {
            $items = [
                'Cognome' => $this->contratto->cognome,
                'Nome' => $this->contratto->nome,
                'Codice fiscale' => $this->contratto->codice_fiscale,
                'Email' => $this->contratto->email,
                'Telefono' => $this->contratto->telefono,
                'Indirizzo' => $this->contratto->indirizzo,
                'Città' => Comune::find($this->contratto->citta)?->comuneConTarga(),
                'Cap' => $this->contratto->cap,
                'Tipo documento' => $this->contratto->tipo_documento ? ContrattoTelefonia::TIPI_DOCUMENTO[$this->contratto->tipo_documento] : '',
                'Numero documento' => $this->contratto->numero_documento,
                'Rilasciato da' => $this->contratto->rilasciato_da,
                'Data rilascio' => $this->contratto->data_rilascio?->format('d/m/Y'),
                'Data scadenza' => $this->contratto->data_scadenza?->format('d/m/Y'),
            ];

            if ($this->contratto->prodotto_type == 'App\Models\ProdottoTimWifi') {
                $items['Pagamento bollettino postale'] = siNo($this->contratto->prodotto->pagamento_bollettino);
            }

            $sections[] = [
                'title' => 'Dati contratto',
                'items' => $this->compactRows($items),
            ];
        }

        $conteggio = count($this->contratto->allegati);
        $email = $this->gestiioMail($subject, [
            'eyebrow' => 'Contratti telefonia',
            'title' => 'Nuovo contratto da lavorare',
            'preheader' => $this->contratto->tipoContratto->nome.' per '.$this->contratto->nominativo(),
            'intro' => 'È stato inviato un nuovo contratto. Trovi sotto il contesto, i dati essenziali e l’indicazione degli allegati presenti.',
            'summary' => [
                'Cliente' => $this->contratto->nominativo(),
                'Servizio' => $this->contratto->tipoContratto->nome,
                'Gestore' => $this->contratto->tipoContratto->gestore->nome ?? $this->contratto->tipoContratto->gestore->titolo ?? 'Gestore',
                'Documenti' => $conteggio ? $conteggio.' in allegato' : 'Nessun allegato',
            ],
            'sections' => $sections,
            'note' => $conteggio ? 'I documenti sono allegati a questa email.' : null,
            'signature' => $this->contratto->agente?->nominativo() ?? config('mail.from.name'),
        ]);

        foreach ($this->contratto->allegati as $allegato) {
            if ($allegato->path_filename && Storage::exists($allegato->path_filename)) {
                $email->attach(Storage::path($allegato->path_filename));

                continue;
            }

            if ($allegato->file_contenuto_base64) {
                $contenuto = base64_decode($allegato->file_contenuto_base64, true);
                if ($contenuto !== false) {
                    $email->attachData($contenuto, $allegato->filename_originale ?: 'allegato', [
                        'mime' => $allegato->mime_type ?: 'application/octet-stream',
                    ]);
                }
            }
        }

        if ($this->contratto->tipoContratto->pda) {
            // Allega PDA
            $classe = 'App\Http\MieClassi\Pdf'.$this->contratto->tipoContratto->pda;
            $pdf = new $classe;
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

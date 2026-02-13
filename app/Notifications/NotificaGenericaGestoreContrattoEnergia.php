<?php

namespace App\Notifications;

use App\Http\Controllers\Backend\SottoClassiEnergia\ProdottoEnergiaAbstract;
use App\Notifications\Concerns\UsesPersonalizedMailSender;
use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\ContrattoEnergia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use PharIo\Manifest\Email;
use function App\importo;
use function App\siNo;

class NotificaGenericaGestoreContrattoEnergia extends Notification
{
    use Queueable;
    use UsesPersonalizedMailSender;

    /**
     * Create a new notification instance.
     *
     * @param ContrattoEnergia $contratto
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
        $email = (new MailMessage);
        if ($this->contratto->gestore->titolo_notifica_a_gestore) {
            $email->subject($this->contratto->gestore->titolo_notifica_a_gestore . ' - per ' . $this->contratto->nominativo());
        } else {
            $email->subject('Nuovo contratto energia ' . $this->contratto->gestore->nome . ' per ' . $this->contratto->nominativo());
        }

        if ($this->contratto->gestore->testo_notifica_a_gestore) {
            $email->line($this->contratto->gestore->testo_notifica_a_gestore);
        }


        if ($this->contratto->gestore->includi_dati_contratto) {
            $email->line(new HtmlString('<strong>Dati contratto</strong>'));


            if ($this->contratto->gestore->model_prodotto) {

                $prodotto = $this->contratto->prodotto;
                switch ($this->contratto->gestore->model_prodotto) {

                    case 'ProdottoEnergiaEgea':

                        $email->line('Cognome: ' . $prodotto->cognome);
                        $email->line('Nome: ' . $prodotto->nome);
                        $email->line('Codice fiscale: ' . $this->contratto->codice_fiscale);
                        $email->line('Email: ' . $this->contratto->email);
                        $email->line('Telefono: ' . $this->contratto->telefono);
                        $email->line('Indirizzo: ' . $prodotto->indirizzo);
                        $email->line('Città: ' . Comune::find($prodotto->citta)?->comuneConTarga());
                        $email->line('Cap: ' . $prodotto->cap);
                        if ($this->contratto->tipo_documento) {
                            $email->line('Tipo documento: ' . $prodotto->tipo_documento ? ContrattoEnergia::TIPI_DOCUMENTO[$this->contratto->tipo_documento] : '');
                            $email->line('Numero documento: ' . $prodotto->numero_documento);
                            $email->line('Rilasciato da: ' . $prodotto->rilasciato_da);
                            $email->line('Data rilascio: ' . $prodotto->data_rilascio?->format('d/m/Y'));
                            $email->line('Data scadenza: ' . $prodotto->data_scadenza?->format('d/m/Y'));
                        }
                        $email->line(' ');

                        $email->line('chiede_esecuzione_anticipata:' . siNo($prodotto->chiede_esecuzione_anticipata));
                        $email->line('residente_fornitura:' . siNo($prodotto->residente_fornitura));

                        //Indirizzo fatturazione
                        $email->line('spedizione_fattura:' . $prodotto->spedizione_fattura);
                        $email->line('indirizzo_fatturazione:' . $prodotto->indirizzo_fatturazione);
                        $email->line('cap_fatturazione:' . $prodotto->cap_fatturazione);
                        $email->line('comune_fatturazione:' . Comune::find($prodotto->comune_fatturazione)?->comuneConTarga());
                        $email->line('nominativo_residente_fatturazione:' . $prodotto->nominativo_residente_fatturazione);

                        //Mandato per addebito diretto SEPA - Dati bancari (da compilare in stampatello)
                        $email->line('banca:' . $prodotto->banca);
                        $email->line('agenzia_filiale:' . $prodotto->agenzia_filiale);
                        $email->line('iban:' . $prodotto->iban);
                        $email->line('bic_swift:' . $prodotto->bic_swift);

                        //Dati del punto fornitura di gas metano
                        $email->line('tipo_attivazione_gas:' . $prodotto->tipo_attivazione_gas);
                        $email->line('pdr:' . $prodotto->pdr);
                        $email->line('matricola_contatore:' . $prodotto->matricola_contatore);
                        $email->line('cat_uso_arera:' . $prodotto->cat_uso_arera);
                        $email->line('cabina_remi:' . $prodotto->cabina_remi);
                        $email->line('tipologia_pdr:' . $prodotto->tipologia_pdr);
                        $email->line('distributore_locale:' . $prodotto->distributore_locale);
                        $email->line('soc_vendita_attuale_gas:' . $prodotto->soc_vendita_attuale_gas);
                        $email->line('mercato_attuale_gas:' . $prodotto->mercato_attuale_gas);
                        $email->line('potenzialita_impianto:' . $prodotto->potenzialita_impianto);
                        $email->line('consumo_anno_termico:' . $prodotto->consumo_anno_termico);

                        //Dati del punto di fornitura di energia elettrica
                        $email->line('tipo_attivazione_luce:' . $prodotto->tipo_attivazione_luce);
                        $email->line('pod:' . $prodotto->pod);
                        $email->line('tipologia_uso:' . $prodotto->tipologia_uso);
                        $email->line('tensione:' . $prodotto->tensione);
                        $email->line('potenza_contrattuale:' . $prodotto->potenza_contrattuale);
                        $email->line('consumo_anno:' . $prodotto->consumo_anno);
                        $email->line('mercato_provenienza_luce:' . $prodotto->mercato_provenienza_luce);
                        $email->line('soc_vendita_attuale_luce:' . $prodotto->soc_vendita_attuale_luce);
                        $email->line('mercato_attuale_luce:' . $prodotto->mercato_attuale_luce);

                        //Indirizzo di fornitura
                        $email->line('indirizzo_fornitura:' . $prodotto->indirizzo_fornitura);
                        $email->line('cap_fornitura:' . $prodotto->cap_fornitura);
                        $email->line('comune_fornitura:' . Comune::find($prodotto->comune_fornitura)?->comuneConTarga());

                        $email->line('dichiara_di_essere:' . $prodotto->dichiara_di_essere);


                        break;

                    case 'ProdottoEnergiaIllumia':

                        $email->line('Cognome: ' . $prodotto->cognome);
                        $email->line('Nome: ' . $prodotto->nome);
                        $email->line('Codice fiscale: ' . $this->contratto->codice_fiscale);
                        $email->line('Email: ' . $this->contratto->email);
                        $email->line('Telefono: ' . $this->contratto->telefono);
                        $email->line('Indirizzo: ' . $prodotto->indirizzo);
                        $email->line('Città: ' . Comune::find($prodotto->citta)?->comuneConTarga());
                        $email->line('Cap: ' . $prodotto->cap);
                        if ($this->contratto->tipo_documento) {
                            $email->line('Tipo documento: ' . $prodotto->tipo_documento ? ContrattoEnergia::TIPI_DOCUMENTO[$this->contratto->tipo_documento] : '');
                            $email->line('Numero documento: ' . $prodotto->numero_documento);
                            $email->line('Rilasciato da: ' . $prodotto->rilasciato_da);
                            $email->line('Data rilascio: ' . $prodotto->data_rilascio?->format('d/m/Y'));
                            $email->line('Data scadenza: ' . $prodotto->data_scadenza?->format('d/m/Y'));
                        }
                        $email->line(' ');

                        $email->line('fornitura_richiesta:' . $prodotto->fornituraRichiestaArray());
                        $email->line('fasce_reperibilita:' . $prodotto->fasceReperibilitaArray());

                        if (in_array('luce', $prodotto->fornitura_richiesta)) {
                            //DATI TECNICI ENERGIA ELETTRICA
                            $email->line('attuale_fornitore_luce:' . $prodotto->attuale_fornitore_luce);
                            $email->line('pod:' . $prodotto->pod);
                            $email->line('indirizzo_fornitura_luce:' . $prodotto->indirizzo_fornitura_luce);
                            $email->line('civico_fornitura_luce:' . $prodotto->civico_fornitura_luce);
                            $email->line('comune_fornitura_luce:' . Comune::find($prodotto->comune_fornitura_luce)?->comuneConTarga());
                            $email->line('cap_fornitura_luce:' . $prodotto->cap_fornitura_luce);
                        }

                        if (in_array('gas', $prodotto->fornitura_richiesta)) {
                            //DATI TECNICI GAS NATURALE
                            $email->line('attuale_fornitore_gas:' . $prodotto->attuale_fornitore_gas);
                            $email->line('pdr:' . $prodotto->pdr);
                            $email->line('tipologia_uso_gas:' . $prodotto->tipologiaUsoGasArray());
                            $email->line('codice_remi:' . $prodotto->codice_remi);
                            $email->line('indirizzo_fornitura_gas:' . $prodotto->indirizzo_fornitura_gas);
                            $email->line('civico_fornitura_gas:' . $prodotto->civico_fornitura_gas);
                            $email->line('comune_fornitura_gas' . Comune::find($prodotto->comune_fornitura_gas)?->comuneConTarga());
                            $email->line('cap_fornitura_gas:' . $prodotto->cap_fornitura_gas);
                        }
                        //MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA
                        $email->line('modalita_pagamento_fattura:' . $prodotto->modalita_pagamento_fattura);
                        $email->line('intestatario_conto_corrente:' . $prodotto->intestatario_conto_corrente);
                        $email->line('codice_fiscale_intestatario:' . $prodotto->codice_fiscale_intestatario);
                        $email->line('iban:' . $prodotto->iban);
                        $email->line('modalita_spedizione_fattura:' . $prodotto->modalita_spedizione_fattura);
                        $email->line('indirizzo_spedizione_fattura:' . $prodotto->indirizzo_spedizione_fattura);
                        $email->line('civico_spedizione_fattura:' . $prodotto->civico_spedizione_fattura);
                        $email->line('comune_spedizione_fattura' . Comune::find($prodotto->comune_spedizione_fattura)?->comuneConTarga());
                        $email->line('cap_spedizione_fattura:' . $prodotto->cap_spedizione_fattura);
                        $email->line('c_o:' . $prodotto->c_o);
                        $email->line('virtu_titolo:' . $prodotto->virtu_titolo);
                        break;

                    case 'ProdottoEnergiaEnelConsumer':
                    case 'ProdottoEnergiaEnelBusiness':
                        $classeProdotto = ProdottoEnergiaAbstract::constructor($this->contratto->gestore->model_prodotto);
                        $classeProdotto->completaNotifica($email, $this->contratto);
                        break;
                }

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

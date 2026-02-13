<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Http\Controllers\Controller;

use App\Models\Comune;
use App\Models\ContrattoEnergia;
use App\Models\GestoreContrattoEnergia;
use App\Models\ProdottoEnergiaEnelBusiness;
use Illuminate\Http\Request;
use function App\siNo;

class EnelBusiness extends ProdottoEnergiaAbstract
{


    /**
     * @param EnelBusiness $model
     * @param Request $request
     * @return mixed
     */
    public function salvaDatiProdotto($contrattoEnergia, $request)
    {

        $model = $contrattoEnergia->prodotto;
        $nuovo = false;
        if (!$model) {
            $nuovo = true;
            $model = new ProdottoEnergiaEnelBusiness();
        }

        //Ciclo su campi
        $campi = [
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'scala' => '',
            'interno' => '',
            'partita_iva' => '',
            'forma_giuridica' => '',
            'cellulare' => '',
            'fax' => '',
            'nome_cognome_referente' => '',
            'codice_fiscale_referente' => '',
            'telefono_referente' => '',
            'indirizzo_sede' => '',
            'comune_sede' => '',
            'nr_sede' => '',
            'cap_sede' => '',
            'c_o' => '',
            'indirizzo_fatturazione' => '',
            'nr_fatturazione' => '',
            'cap_fatturazione' => '',
            'comune_fatturazione' => '',
            'codice_destinatario' => '',
            'data_inizio_validita' => 'app\getInputData',
            'data_fine_validita' => 'app\getInputData',
            'cig' => '',
            'cup' => '',
            'pod' => '',
            'provenienza_mercato_libero' => 'app\getInputCheckbox',
            'uso_non_professionale_luce' => 'app\getInputCheckbox',
            'consumo_annuo_luce' => '',
            'potenza_contrattuale' => '',
            'livello_tensione' => '',
            'attuale_societa_luce' => '',
            'indirizzo_fornitura_luce' => '',
            'nr_fornitura_luce' => '',
            'cap_fornitura_luce' => '',
            'comune_fornitura_luce' => '',
            'pdr' => '',
            'uso_non_professionale_gas' => 'app\getInputCheckbox',
            'attuale_societa_gas' => '',
            'profilo_consumo' => '',
            'posizione_contatore' => '',
            'consumo_annuo' => '',
            'matricola_contatore' => '',
            'indirizzo_fornitura_gas' => '',
            'nr_fornitura_gas' => '',
            'cap_fornitura_gas' => '',
            'comune_fornitura_gas' => '',
            'modalita_pagamento' => '',
            'invio_fattura' => '',
            'titolare_cc' => '',
            'codice_fiscale_titolare' => '',
            'cognome_nome_sottoscrittore' => '',
            'recapito_telefonico_titolare' => '',
            'iban' => '',
            'iban_sepa' => '',
            'tipo_documento' => '',
            'numero_documento' => '',
            'rilasciato_da' => '',
            'data_rilascio' => 'app\getInputData',
            'data_scadenza' => 'app\getInputData',
            'voltura_ordinaria_contestuale' => 'app\getInputCheckbox',
            'voltura_mortis_causa' => 'app\getInputCheckbox',
            'bolletta_web' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        if ($nuovo) {
            $contrattoEnergia->prodotto_id = $model->contratto_energia_id;
            $contrattoEnergia->prodotto_type = get_class($model);
        }
        $contrattoEnergia->denominazione = $request->input('denominazione');
        $contrattoEnergia->indirizzo_completo = Comune::find($model->comune_sede)?->comuneConTarga();
        $contrattoEnergia->testo_ricerca = $contrattoEnergia->denominazione . '|' . $contrattoEnergia->codice_contratto . '|' . $model->partita_iva;
        $contrattoEnergia->save();

        return $model;
    }


    public function rulesProdotto($id = null)
    {


        $rules = [
            'indirizzo' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'cap' => ['nullable'],
            'scala' => ['nullable'],
            'interno' => ['nullable'],
            'partita_iva' => ['nullable', new \App\Rules\PartitaIvaRule()],
            'forma_giuridica' => ['nullable', 'max:255'],
            'cellulare' => ['nullable', 'max:255'],
            'fax' => ['nullable', 'max:255'],
            'nome_cognome_referente' => ['nullable', 'max:255'],
            'codice_fiscale_referente' => ['nullable', 'max:255'],
            'telefono_referente' => ['nullable', 'max:255'],
            'indirizzo_sede' => ['nullable', 'max:255'],
            'nr_sede' => ['nullable', 'max:255'],
            'cap_sede' => ['nullable'],
            'comune_sede' => ['nullable', 'max:255'],
            'c_o' => ['nullable', 'max:255'],
            'indirizzo_fatturazione' => ['nullable', 'max:255'],
            'nr_fatturazione' => ['nullable', 'max:255'],
            'cap_fatturazione' => ['nullable'],
            'comune_fatturazione' => ['nullable', 'max:255'],
            'codice_destinatario' => ['nullable'],
            'data_inizio_validita' => ['nullable'],
            'data_fine_validita' => ['nullable'],
            'cig' => ['nullable', 'max:255'],
            'cup' => ['nullable', 'max:255'],
            'pod' => ['nullable', 'max:255'],
            'provenienza_mercato_libero' => ['nullable'],
            'uso_non_professionale_luce' => ['nullable'],
            'consumo_annuo_luce' => ['nullable', 'max:255'],
            'potenza_contrattuale' => ['nullable', 'max:255'],
            'livello_tensione' => ['nullable', 'max:255'],
            'attuale_societa_luce' => ['nullable', 'max:255'],
            'indirizzo_fornitura_luce' => ['nullable', 'max:255'],
            'nr_fornitura_luce' => ['nullable', 'max:255'],
            'cap_fornitura_luce' => ['nullable'],
            'comune_fornitura_luce' => ['nullable', 'max:255'],
            'pdr' => ['nullable', 'max:255'],
            'uso_non_professionale_gas' => ['nullable'],
            'attuale_societa_gas' => ['nullable', 'max:255'],
            'profilo_consumo' => ['nullable', 'max:255'],
            'posizione_contatore' => ['nullable', 'max:255'],
            'consumo_annuo' => ['nullable'],
            'matricola_contatore' => ['nullable', 'max:255'],
            'indirizzo_fornitura_gas' => ['nullable', 'max:255'],
            'nr_fornitura_gas' => ['nullable', 'max:255'],
            'cap_fornitura_gas' => ['nullable'],
            'comune_fornitura_gas' => ['nullable', 'max:255'],
            'modalita_pagamento' => ['nullable', 'max:255'],
            'invio_fattura' => ['nullable', 'max:255'],
            'titolare_cc' => ['nullable', 'max:255'],
            'codice_fiscale_titolare' => ['nullable', 'max:255'],
            'cognome_nome_sottoscrittore' => ['nullable', 'max:255'],
            'recapito_telefonico_titolare' => ['nullable', 'max:255'],
            'iban' => ['nullable', new \App\Rules\IbanRule()],
            'iban_sepa' => ['nullable', 'max:255'],
            'tipo_documento' => ['nullable', 'max:255'],
            'numero_documento' => ['nullable', 'max:255'],
            'rilasciato_da' => ['nullable', 'max:255'],
            'data_rilascio' => ['nullable'],
            'data_scadenza' => ['nullable'],
        ];

        return $rules;
    }


    public function determinaProvvigione(Request $request)
    {
        $gestoreContrattoEnergia = GestoreContrattoEnergia::find(4);
        if ($request->input('modalita_pagamento') == 'bollettino') {
            return $gestoreContrattoEnergia->importo_pagamento_bollettino;
        } else {
            return $gestoreContrattoEnergia->importo_contratto;
        }
    }


    public function completaNotifica($email, $contratto)
    {
        $email->line('Nome Cognome / Ragione sociale: ' . $contratto->denominazione);
        $email->line('Forma giuridica: ' . $contratto->prodotto->forma_giuridica);
        $email->line('Codice fiscale: ' . $contratto->codice_fiscale);
        $email->line('Email: ' . $contratto->email);
        $email->line('Telefono: ' . $contratto->telefono);
        $email->line('Indirizzo: ' . $contratto->prodotto->indirizzo);
        $email->line('Città: ' . Comune::find($contratto->prodotto->citta)?->comuneConTarga());
        $email->line('Cap: ' . $contratto->prodotto->cap);


        $email->line('-REFERENTE/AMM.RE CONDOMINIO');
        $email->line('Nome cognome: ' . $contratto->prodotto->nome_cognome_referente);
        $email->line('Telefono: ' . $contratto->prodotto->telefono_referente);
        $email->line('Codice fiscale: ' . $contratto->prodotto->codice_fiscale_referente);
        if ($contratto->prodotto->tipo_documento) {
            $email->line('Tipo documento: ' . $contratto->prodotto->tipo_documento ? ContrattoEnergia::TIPI_DOCUMENTO[$contratto->prodotto->tipo_documento] : '');
            $email->line('Numero documento: ' . $contratto->prodotto->numero_documento);
            $email->line('Rilasciato da: ' . $contratto->prodotto->rilasciato_da);
            $email->line('Data rilascio: ' . $contratto->prodotto->data_rilascio?->format('d/m/Y'));
            $email->line('Data scadenza: ' . $contratto->prodotto->data_scadenza?->format('d/m/Y'));
        }
        $email->line('-INDIRIZZO SEDE LEGALE');

        $email->line('indirizzo_sede:' . siNo($contratto->prodotto->indirizzo_sede));
        $email->line('nr_sede:' . $contratto->prodotto->nr_sede);
        $email->line('cap_sede:' . $contratto->prodotto->cap_sede);
        $email->line('comune_sede:' . Comune::find($contratto->prodotto->comune_sede)?->comuneConTarga());

        $email->line('--COMPLIARE SE DIVERSO DALLA SEDE DEL CLIENTE');
        $email->line('c_o:' . siNo($contratto->prodotto->c_o));
        $email->line('indirizzo_fatturazione:' . $contratto->prodotto->indirizzo_fatturazione);
        $email->line('nr_fatturazione:' . $contratto->prodotto->nr_fatturazione);
        $email->line('cap_fatturazione:' . $contratto->prodotto->cap_fatturazione);
        $email->line('comune_fatturazione:' . Comune::find($contratto->prodotto->comune_fatturazione)?->comuneConTarga());

        $email->line('codice_destinatario:' . $contratto->prodotto->codice_destinatario);
        $email->line('data_inizio_validita:' . $contratto->prodotto->data_inizio_validita?->format('d/m/Y'));
        $email->line('data_fine_validita:' . $contratto->prodotto->data_fine_validita?->format('d/m/Y'));
        $email->line('cig:' . $contratto->prodotto->cig);
        $email->line('cup:' . $contratto->prodotto->cup);


        $email->line('--ENERGIA ELETTRICA');
        //DATI TECNICI ENERGIA ELETTRICA
        $email->line('pod:' . $contratto->prodotto->pod);
        $email->line('provenienza_mercato_libero:' . siNo($contratto->prodotto->provenienza_mercato_libero));
        $email->line('uso_non_professionale_luce:' . siNo($contratto->prodotto->uso_non_professionale_luce));
        $email->line('consumo_annuo_luce:' . $contratto->prodotto->consumo_annuo_luce);
        $email->line('potenza_contrattuale:' . $contratto->prodotto->potenza_contrattuale);
        $email->line('livello_tensione:' . $contratto->prodotto->livello_tensione);
        $email->line('attuale_societa_luce:' . $contratto->prodotto->attuale_societa_luce);
        $email->line('indirizzo_fornitura_luce:' . $contratto->prodotto->indirizzo_fornitura_luce);
        $email->line('nr_fornitura_luce:' . $contratto->prodotto->nr_fornitura_luce);
        $email->line('cap_fornitura_luce:' . $contratto->prodotto->cap_fornitura_luce);
        $email->line('comune_fornitura_luce:' . Comune::find($contratto->prodotto->comune_fornitura_luce)?->comuneConTarga());

        $email->line('--GAS NATURALE');
        //DATI TECNICI GAS NATURALE
        $email->line('pdr:' . $contratto->prodotto->pdr);
        $email->line('uso_non_professionale_gas:' . siNo($contratto->prodotto->uso_non_professionale_gas));
        $email->line('attuale_societa_gas:' . $contratto->prodotto->attuale_societa_gas);
        $email->line('profilo_consumo:' . ($contratto->prodotto->profilo_consumo ? ProdottoEnergiaEnelBusiness::PROFILI_CONSUMO[$contratto->prodotto->profilo_consumo] : null));
        $email->line('posizione_contatore:' . siNo($contratto->prodotto->posizione_contatore));
        $email->line('consumo_annuo:' . siNo($contratto->prodotto->consumo_annuo));
        $email->line('matricola_contatore:' . siNo($contratto->prodotto->matricola_contatore));
        $email->line('indirizzo_fornitura_gas:' . $contratto->prodotto->indirizzo_fornitura_gas);
        $email->line('nr_fornitura_gas:' . $contratto->prodotto->nr_fornitura_gas);
        $email->line('cap_fornitura_gas:' . $contratto->prodotto->cap_fornitura_gas);
        $email->line('comune_fornitura_gas:' . Comune::find($contratto->prodotto->comune_fornitura_gas)?->comuneConTarga());

        //MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA
        $email->line('modalita_pagamento:' . $contratto->prodotto->modalita_pagamento);
        $email->line('invio_fattura:' . $contratto->prodotto->invio_fattura);


        $email->line('titolare_cc:' . $contratto->prodotto->titolare_cc);
        $email->line('codice_fiscale_titolare:' . $contratto->prodotto->codice_fiscale_titolare);
        $email->line('cognome_nome_sottoscrittore:' . $contratto->prodotto->cognome_nome_sottoscrittore);
        $email->line('recapito_telefonico_titolare:' . $contratto->prodotto->recapito_telefonico_titolare);
        $email->line('iban:' . $contratto->prodotto->iban);
        $email->line('iban_sepa:' . $contratto->prodotto->iban_sepa);
        $email->line('iban_sepa:' . $contratto->prodotto->iban_sepa);
        $email->line('voltura_ordinaria_contestuale:' . siNo($contratto->prodotto->voltura_ordinaria_contestuale));
        $email->line('voltura_mortis_causa:' . siNo($contratto->prodotto->voltura_mortis_causa));
        $email->line('Bolletta web:' . siNo($contratto->prodotto->bolletta_web));


    }


}

<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Http\Controllers\Controller;

use App\Models\Comune;
use App\Models\ContrattoEnergia;
use App\Models\GestoreContrattoEnergia;
use App\Models\ProdottoEnergiaEnelConsumer;
use Illuminate\Http\Request;
use function App\siNo;

class EnelConsumer extends ProdottoEnergiaAbstract
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
            $model = new ProdottoEnergiaEnelConsumer();
        }

        //Ciclo su campi
        $campi = [
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',

            'indirizzo' => 'app\getInputUcwords',
            'interno' => '',
            'citta' => '',
            'cap' => '',
            'scala' => '',

            'tipo_documento' => '',
            'numero_documento' => 'strtoupper',
            'rilasciato_da' => '',
            'data_rilascio' => 'App\getInputData',
            'data_scadenza' => 'App\getInputData',

            'residente_fornitura' => 'app\getInputCheckbox',
            'indirizzo_fornitura' => '',
            'nr_fornitura' => '',
            'scala_fornitura' => '',
            'interno_fornitura' => '',
            'cap_fornitura' => '',
            'comune_fornitura' => '',
            'indirizzo_fatturazione' => '',
            'presso_fatturazione' => '',
            'nr_fatturazione' => '',
            'scala_fatturazione' => '',
            'interno_fatturazione' => '',
            'cap_fatturazione' => '',
            'comune_fatturazione' => '',
            'pod' => '',
            'provenienza_mercato_libero' => 'app\getInputCheckbox',
            'consumo_annuo_luce' => '',
            'potenza_contrattuale' => '',
            'attuale_societa_luce' => '',
            'pdr' => '',
            'consumo_annuo_gas' => '',
            'attuale_societa_gas' => '',
            'riscaldamento' => 'app\getInputCheckbox',
            'cottura_acqua_calda' => 'app\getInputCheckbox',
            'codice_destinatario' => '',
            'indirizzo_pec' => '',
            'modalita_pagamento' => '',
            'invio_fattura' => '',
            'titolare_cc' => '',
            'codice_fiscale_titolare' => '',
            'telefono_titolare' => '',
            'iban' => '',
            'bic_swift' => '',
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
        $contrattoEnergia->denominazione = $model->cognome . ' ' . $model->nome;
        $contrattoEnergia->indirizzo_completo = Comune::find($model->citta)?->comuneConTarga();
        $contrattoEnergia->testo_ricerca = $contrattoEnergia->denominazione . '|' . $contrattoEnergia->codice_contratto;

        $contrattoEnergia->save();

        return $model;
    }


    public function rulesProdotto($id = null)
    {


        $rules = [
            'residente_fornitura' => ['nullable'],
            'indirizzo_fornitura' => ['nullable', 'max:255'],
            'nr_fornitura' => ['nullable', 'max:255'],
            'scala_fornitura' => ['nullable', 'max:255'],
            'interno_fornitura' => ['nullable', 'max:255'],
            'cap_fornitura' => ['nullable'],
            'comune_fornitura' => ['nullable', 'max:255'],
            'indirizzo_fatturazione' => ['nullable', 'max:255'],
            'presso_fatturazione' => ['nullable', 'max:255'],
            'nr_fatturazione' => ['nullable', 'max:255'],
            'scala_fatturazione' => ['nullable', 'max:255'],
            'interno_fatturazione' => ['nullable', 'max:255'],
            'cap_fatturazione' => ['nullable'],
            'comune_fatturazione' => ['nullable', 'max:255'],
            'pod' => ['nullable', 'max:255'],
            'provenienza_mercato_libero' => ['nullable'],
            'consumo_annuo_luce' => ['nullable', 'max:255'],
            'potenza_contrattuale' => ['nullable', 'max:255'],
            'attuale_societa_luce' => ['nullable', 'max:255'],
            'pdr' => ['nullable', 'max:255'],
            'consumo_annuo_gas' => ['nullable', 'max:255'],
            'attuale_societa_gas' => ['nullable', 'max:255'],
            'riscaldamento' => ['nullable'],
            'cottura_acqua_calda' => ['nullable'],
            'codice_destinatario' => ['nullable', 'max:255'],
            'indirizzo_pec' => ['nullable', 'max:255'],
            'modalita_pagamento' => ['nullable', 'max:255'],
            'invio_fattura' => ['nullable', 'max:255'],
            'titolare_cc' => ['nullable', 'max:255'],
            'codice_fiscale_titolare' => ['nullable', 'max:255'],
            'telefono_titolare' => ['nullable', 'max:255'],
            'iban' => ['nullable', new \App\Rules\IbanRule()],
            'bic_swift' => ['nullable', 'max:255'],
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
        $email->line('Cognome: ' . $contratto->prodotto->cognome);
        $email->line('Nome: ' . $contratto->prodotto->nome);
        $email->line('Codice fiscale: ' . $contratto->codice_fiscale);
        $email->line('Email: ' . $contratto->email);
        $email->line('Telefono: ' . $contratto->telefono);
        $email->line('Indirizzo: ' . $contratto->prodotto->indirizzo);
        $email->line('Città: ' . Comune::find($contratto->prodotto->citta)?->comuneConTarga());
        $email->line('Cap: ' . $contratto->prodotto->cap);
        if ($contratto->tipo_documento) {
            $email->line('Tipo documento: ' . $contratto->prodotto->tipo_documento ? ContrattoEnergia::TIPI_DOCUMENTO[$contratto->tipo_documento] : '');
            $email->line('Numero documento: ' . $contratto->prodotto->numero_documento);
            $email->line('Rilasciato da: ' . $contratto->prodotto->rilasciato_da);
            $email->line('Data rilascio: ' . $contratto->prodotto->data_rilascio?->format('d/m/Y'));
            $email->line('Data scadenza: ' . $contratto->prodotto->data_scadenza?->format('d/m/Y'));
        }
        $email->line(' ');

        $email->line('residente_fornitura:' . siNo($contratto->prodotto->residente_fornitura));
        $email->line('indirizzo_fornitura:' . $contratto->prodotto->indirizzo_fornitura);
        $email->line('nr_fornitura:' . $contratto->prodotto->nr_fornitura);
        $email->line('scala_fornitura:' . $contratto->prodotto->scala_fornitura);
        $email->line('interno_fornitura:' . $contratto->prodotto->interno_fornitura);
        $email->line('comune_fornitura:' . Comune::find($contratto->prodotto->comune_fornitura)?->comuneConTarga());
        $email->line('cap_fornitura:' . $contratto->prodotto->cap_fornitura);

        $email->line('indirizzo_fatturazione:' . $contratto->prodotto->indirizzo_fatturazione);
        $email->line('presso_fatturazione:' . $contratto->prodotto->presso_fatturazione);
        $email->line('nr_fatturazione:' . $contratto->prodotto->nr_fatturazione);
        $email->line('scala_fatturazione:' . $contratto->prodotto->scala_fatturazione);
        $email->line('interno_fatturazione:' . $contratto->prodotto->interno_fatturazione);

        $email->line('comune_fatturazione:' . Comune::find($contratto->prodotto->comune_fatturazione)?->comuneConTarga());
        $email->line('cap_fatturazione:' . $contratto->prodotto->cap_fatturazione);

        //DATI TECNICI ENERGIA ELETTRICA
        $email->line('pod:' . $contratto->prodotto->pod);
        $email->line('provenienza_mercato_libero:' . siNo($contratto->prodotto->provenienza_mercato_libero));
        $email->line('consumo_annuo_luce:' . $contratto->prodotto->consumo_annuo_luce);
        $email->line('potenza_contrattuale:' . $contratto->prodotto->potenza_contrattuale);
        $email->line('attuale_societa_luce:' . $contratto->prodotto->attuale_societa_luce);


        //DATI TECNICI GAS NATURALE
        $email->line('pdr:' . $contratto->prodotto->pdr);
        $email->line('consumo_annuo_gas:' . $contratto->prodotto->consumo_annuo_gas);
        $email->line('attuale_societa_gas:' . $contratto->prodotto->attuale_societa_gas);
        $email->line('riscaldamento:' . siNo($contratto->prodotto->riscaldamento));
        $email->line('cottura_acqua_calda:' . siNo($contratto->prodotto->cottura_acqua_calda));

        //MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA
        $email->line('codice_destinatario:' . $contratto->prodotto->codice_destinatario);
        $email->line('indirizzo_pec:' . $contratto->prodotto->indirizzo_pec);
        $email->line('modalita_pagamento:' . $contratto->prodotto->modalita_pagamento);
        $email->line('invio_fattura:' . $contratto->prodotto->invio_fattura);
        $email->line('titolare_cc:' . $contratto->prodotto->titolare_cc);
        $email->line('codice_fiscale_titolare:' . $contratto->prodotto->codice_fiscale_titolare);
        $email->line('telefono_titolare:' . $contratto->prodotto->telefono_titolare);
        $email->line('iban:' . $contratto->prodotto->iban);
        $email->line('bic_swift:' . $contratto->prodotto->bic_swift);

        $email->line('voltura_ordinaria_contestuale:' . siNo($contratto->prodotto->voltura_ordinaria_contestuale));
        $email->line('voltura_mortis_causa:' . siNo($contratto->prodotto->voltura_mortis_causa));
        $email->line('Bolletta web:' . siNo($contratto->prodotto->bolletta_web));

    }


}

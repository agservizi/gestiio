<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Models\Comune;
use App\Models\ProdottoEnergiaGenerico;
use App\Rules\IbanRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PdrRule;
use App\Rules\PodRule;
use Illuminate\Http\Request;

class Generico extends ProdottoEnergiaAbstract
{
    /**
     * @param  EnelBusiness  $model
     * @param  Request  $request
     * @return mixed
     */
    public function salvaDatiProdotto($contrattoEnergia, $request)
    {

        $model = $contrattoEnergia->prodotto;
        $nuovo = false;
        if (! $model) {
            $nuovo = true;
            $model = new ProdottoEnergiaGenerico;
        }

        // Ciclo su campi
        $campi = [

            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'partita_iva' => '',
            'forma_giuridica' => '',
            'cellulare' => '',
            'fax' => '',
            'nome_cognome_referente' => '',
            'codice_fiscale_referente' => '',
            'telefono_referente' => '',

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

            'fornitura_richiesta' => '',
            'fasce_reperibilita' => '',
            'attuale_fornitore_luce' => '',
            'pod' => '',
            'provenienza_mercato_libero' => 'app\getInputCheckbox',
            'uso_non_professionale_luce' => 'app\getInputCheckbox',
            'consumo_annuo_luce' => '',
            'potenza_contrattuale' => '',
            'livello_tensione' => '',
            'attuale_societa_luce' => '',
            'indirizzo_fornitura_luce' => '',
            'civico_fornitura_luce' => '',
            'comune_fornitura_luce' => '',
            'cap_fornitura_luce' => '',
            'attuale_fornitore_gas' => '',
            'pdr' => '',
            'uso_non_professionale_gas' => 'app\getInputCheckbox',
            'consumo_annuo_gas' => '',
            'attuale_societa_gas' => '',
            'profilo_consumo' => '',
            'posizione_contatore' => '',
            'consumo_annuo' => '',
            'matricola_contatore' => '',
            'riscaldamento' => 'app\getInputCheckbox',
            'cottura_acqua_calda' => 'app\getInputCheckbox',
            'tipologia_uso_gas' => '',
            'codice_remi' => '',
            'indirizzo_fornitura_gas' => '',
            'civico_fornitura_gas' => '',
            'comune_fornitura_gas' => '',
            'cap_fornitura_gas' => '',
            'modalita_pagamento_fattura' => '',
            'intestatario_conto_corrente' => '',
            'codice_fiscale_intestatario' => '',
            'iban' => '',
            'modalita_spedizione_fattura' => '',
            'indirizzo_spedizione_fattura' => '',
            'civico_spedizione_fattura' => '',
            'comune_spedizione_fattura' => '',
            'cap_spedizione_fattura' => '',
            'c_o' => '',
            'virtu_titolo' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        // Su pratiche business nome/cognome possono non essere compilati a form,
        // ma nel DB del prodotto sono NOT NULL.
        if ($model->nome === null) {
            $model->nome = '';
        }
        if ($model->cognome === null) {
            $model->cognome = '';
        }

        $model->save();
        if ($nuovo) {
            $contrattoEnergia->prodotto_id = $model->contratto_energia_id;
            $contrattoEnergia->prodotto_type = get_class($model);
        }
        $isBusiness = $request->input('categoria_pratica') === 'business';
        $denominazione = trim((string) $request->input('denominazione'));
        if (! $isBusiness || $denominazione === '') {
            $denominazione = trim((string) ($model->cognome.' '.$model->nome));
        }
        $contrattoEnergia->denominazione = $denominazione;
        $contrattoEnergia->indirizzo_completo = $model->indirizzo.' '.Comune::find($model->citta)?->comuneConTarga();
        $contrattoEnergia->testo_ricerca = $contrattoEnergia->denominazione.'|'.$contrattoEnergia->codice_contratto;

        $contrattoEnergia->save();

        return $model;
    }

    public function rulesProdotto($id = null)
    {

        $isBusiness = request()->input('categoria_pratica') === 'business';

        $rules = [
            'partita_iva' => $isBusiness ? ['required', new PartitaIvaRule] : ['nullable', new PartitaIvaRule],
            'forma_giuridica' => $isBusiness ? ['required', 'max:255'] : ['nullable', 'max:255'],
            'cellulare' => ['nullable', 'max:255'],
            'fax' => ['nullable', 'max:255'],
            'nome_cognome_referente' => ['nullable', 'max:255'],
            'codice_fiscale_referente' => ['nullable', 'max:255'],
            'telefono_referente' => ['nullable', 'max:255'],
            'fornitura_richiesta' => ['nullable'],
            'fasce_reperibilita' => ['nullable'],
            'attuale_fornitore_luce' => ['nullable', 'max:255'],
            'pod' => ['nullable', new PodRule],
            'provenienza_mercato_libero' => ['nullable'],
            'uso_non_professionale_luce' => ['nullable'],
            'consumo_annuo_luce' => ['nullable', 'max:255'],
            'potenza_contrattuale' => ['nullable', 'max:255'],
            'livello_tensione' => ['nullable', 'max:255'],
            'attuale_societa_luce' => ['nullable', 'max:255'],
            'indirizzo_fornitura_luce' => ['nullable', 'max:255'],
            'civico_fornitura_luce' => ['nullable', 'max:255'],
            'comune_fornitura_luce' => ['nullable', 'max:255'],
            'cap_fornitura_luce' => ['nullable'],
            'attuale_fornitore_gas' => ['nullable', 'max:255'],
            'pdr' => ['nullable', new PdrRule],
            'uso_non_professionale_gas' => ['nullable'],
            'consumo_annuo_gas' => ['nullable', 'max:255'],
            'attuale_societa_gas' => ['nullable', 'max:255'],
            'profilo_consumo' => ['nullable', 'max:255'],
            'posizione_contatore' => ['nullable', 'max:255'],
            'consumo_annuo' => ['nullable', 'max:255'],
            'matricola_contatore' => ['nullable', 'max:255'],
            'riscaldamento' => ['nullable'],
            'cottura_acqua_calda' => ['nullable'],
            'tipologia_uso_gas' => ['nullable'],
            'codice_remi' => ['nullable', 'max:255'],
            'indirizzo_fornitura_gas' => ['nullable', 'max:255'],
            'civico_fornitura_gas' => ['nullable', 'max:255'],
            'comune_fornitura_gas' => ['nullable', 'max:255'],
            'cap_fornitura_gas' => ['nullable'],
            'modalita_pagamento_fattura' => ['nullable', 'max:255'],
            'intestatario_conto_corrente' => ['nullable', 'max:255'],
            'codice_fiscale_intestatario' => ['nullable', 'max:255'],
            'iban' => ['nullable', new IbanRule],
            'modalita_spedizione_fattura' => ['nullable', 'max:255'],
            'indirizzo_spedizione_fattura' => ['nullable', 'max:255'],
            'civico_spedizione_fattura' => ['nullable', 'max:255'],
            'comune_spedizione_fattura' => ['nullable', 'max:255'],
            'cap_spedizione_fattura' => ['nullable', 'max:255'],
            'c_o' => ['nullable', 'max:255'],
            'virtu_titolo' => ['nullable', 'max:255'],
        ];

        return $rules;
    }

    public function determinaProvvigione(Request $request)
    {
        return $this->calcolaProvvigioneDaGestore($request, 1, false);
    }
}

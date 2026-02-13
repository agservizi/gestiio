<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Http\Controllers\Controller;

use App\Models\Comune;
use App\Models\GestoreContrattoEnergia;
use App\Models\ProdottoEnergiaEgea;
use Illuminate\Http\Request;

class Egea extends ProdottoEnergiaAbstract
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
            $model = new ProdottoEnergiaEgea();
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

            'chiede_esecuzione_anticipata' => 'app\getInputCheckbox',
            'residente_fornitura' => 'app\getInputCheckbox',
            'spedizione_fattura' => '',
            'indirizzo_fatturazione' => '',
            'cap_fatturazione' => '',
            'comune_fatturazione' => '',
            'nominativo_residente_fatturazione' => '',
            'banca' => '',
            'agenzia_filiale' => '',
            'iban' => '',
            'bic_swift' => '',
            'tipo_attivazione_gas' => '',
            'pdr' => '',
            'matricola_contatore' => '',
            'cat_uso_arera' => '',
            'cabina_remi' => '',
            'tipologia_pdr' => '',
            'distributore_locale' => '',
            'soc_vendita_attuale_gas' => '',
            'mercato_attuale_gas' => '',
            'potenzialita_impianto' => '',
            'consumo_anno_termico' => '',
            'tipo_attivazione_luce' => '',
            'pod' => '',
            'tipologia_uso' => '',
            'tensione' => '',
            'potenza_contrattuale' => '',
            'consumo_anno' => '',
            'mercato_provenienza_luce' => '',
            'soc_vendita_attuale_luce' => '',
            'mercato_attuale_luce' => '',
            'indirizzo_fornitura' => '',
            'cap_fornitura' => '',
            'comune_fornitura' => '',
            'dichiara_di_essere' => '',
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
        $contrattoEnergia->indirizzo_completo = $model->indirizzo . ' ' . Comune::find($model->citta)?->comuneConTarga();
        $contrattoEnergia->testo_ricerca = $contrattoEnergia->denominazione . '|' . $contrattoEnergia->codice_contratto;
        $contrattoEnergia->save();

        return $model;
    }


    public function rulesProdotto($id = null)
    {


        $rules = [
            'chiede_esecuzione_anticipata' => ['nullable'],
            'residente_fornitura' => ['nullable'],
            'spedizione_fattura' => ['required', 'max:255'],
            'indirizzo_fatturazione' => ['nullable', 'max:255'],
            'cap_fatturazione' => ['nullable'],
            'comune_fatturazione' => ['nullable', 'max:255'],
            'nominativo_residente_fatturazione' => ['nullable', 'max:255'],
            'banca' => ['required', 'max:255'],
            'agenzia_filiale' => ['required', 'max:255'],
            'iban' => ['required', new \App\Rules\IbanRule()],
            'bic_swift' => ['required', 'max:255'],
            'tipo_attivazione_gas' => ['required_without:tipo_attivazione_luce', 'max:255'],
            'pdr' => ['required_with:tipo_attivazione_gas', 'max:255'],
            'matricola_contatore' => ['nullable', 'max:255'],
            'cat_uso_arera' => ['required_with:tipo_attivazione_gas', 'max:255'],
            'cabina_remi' => ['nullable', 'max:255'],
            'tipologia_pdr' => ['required_with:tipo_attivazione_gas', 'max:255'],
            'distributore_locale' => ['nullable', 'max:255'],
            'soc_vendita_attuale_gas' => ['nullable', 'max:255'],
            'mercato_attuale' => ['nullable', 'max:255'],
            'potenzialita_impianto' => ['nullable', 'max:255'],
            'consumo_anno_termico' => ['nullable', 'max:255'],

            'tipo_attivazione_luce' => ['required_without:tipo_attivazione_gas', 'max:255'],
            'pod' => ['required_with:tipo_attivazione_luce', 'max:255'],
            'tipologia_uso' => ['required_with:tipo_attivazione_luce', 'max:255'],
            'tensione' => ['nullable', 'max:255'],
            'potenza_contrattuale' => ['nullable', 'max:255'],
            'consumo_anno' => ['nullable', 'max:255'],
            'mercato_provenienza_luce' => ['nullable', 'max:255'],
            'soc_vendita_attuale_luce' => ['nullable', 'max:255'],
            'mercato_attuale_luce' => ['nullable', 'max:255'],
            'indirizzo_fornitura' => ['nullable', 'max:255'],
            'cap_fornitura' => ['nullable'],
            'comune_fornitura' => ['nullable', 'max:255'],
            'dichiara_di_essere' => ['nullable', 'max:255'],
        ];

        return $rules;
    }


    public function determinaProvvigione(Request $request)
    {
        $gestoreContrattoEnergia = GestoreContrattoEnergia::find(2);
        return $gestoreContrattoEnergia->importo_contratto;
    }


}

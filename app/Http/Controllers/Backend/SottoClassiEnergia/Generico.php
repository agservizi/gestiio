<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Http\Controllers\Controller;

use App\Models\Comune;
use App\Models\GestoreContrattoEnergia;
use App\Models\ProdottoEnergiaGenerico;
use App\Models\ProdottoEnergiaIllumia;
use Illuminate\Http\Request;


class Generico extends ProdottoEnergiaAbstract
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
            $model = new ProdottoEnergiaGenerico();
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

            'fornitura_richiesta' => '',
            'fasce_reperibilita' => '',
            'attuale_fornitore_luce' => '',
            'pod' => '',
            'indirizzo_fornitura_luce' => '',
            'civico_fornitura_luce' => '',
            'comune_fornitura_luce' => '',
            'cap_fornitura_luce' => '',
            'attuale_fornitore_gas' => '',
            'pdr' => '',
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
            'fornitura_richiesta' => ['nullable'],
            'fasce_reperibilita' => ['nullable'],
            'attuale_fornitore_luce' => ['nullable', 'max:255'],
            'pod' => ['nullable', 'max:255'],
            'indirizzo_fornitura_luce' => ['nullable', 'max:255'],
            'civico_fornitura_luce' => ['nullable', 'max:255'],
            'comune_fornitura_luce' => ['nullable', 'max:255'],
            'cap_fornitura_luce' => ['nullable'],
            'attuale_fornitore_gas' => ['nullable', 'max:255'],
            'pdr' => ['nullable', 'max:255'],
            'tipologia_uso_gas' => ['nullable'],
            'codice_remi' => ['nullable', 'max:255'],
            'indirizzo_fornitura_gas' => ['nullable', 'max:255'],
            'civico_fornitura_gas' => ['nullable', 'max:255'],
            'comune_fornitura_gas' => ['nullable', 'max:255'],
            'cap_fornitura_gas' => ['nullable'],
            'modalita_pagamento_fattura' => ['nullable', 'max:255'],
            'intestatario_conto_corrente' => ['nullable', 'max:255'],
            'codice_fiscale_intestatario' => ['nullable', 'max:255'],
            'iban' => ['nullable', new \App\Rules\IbanRule()],
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
        $gestoreContrattoEnergia = GestoreContrattoEnergia::find(1);
        return $gestoreContrattoEnergia->importo_contratto;
    }


}

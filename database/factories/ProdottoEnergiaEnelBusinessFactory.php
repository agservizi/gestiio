<?php

namespace Database\Factories;

use App\Models\Comune;
use App\Models\ProdottoEnergiaEnelBusiness;
use Database\Seeders\CfPiRandom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProdottoEnergiaEnelBusiness>
 */
class ProdottoEnergiaEnelBusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'contratto_energia_id' => null,
            'indirizzo' => $this->faker->streetName(),
            'citta' => Comune::inRandomOrder()->first()->id,
            'cap' => rand(11111, 55555),
            'scala' => null,
            'interno' => null,
            'partita_iva' => CfPiRandom::getPartitaIva(),
            'forma_giuridica' => null,
            'cellulare' => null,
            'fax' => null,
            'nome_cognome_referente' => null,
            'codice_fiscale_referente' => null,
            'telefono_referente' => null,
            'indirizzo_sede' => null,
            'nr_sede' => null,
            'cap_sede' => null,
            'comune_sede' => null,
            'c_o' => null,
            'indirizzo_fatturazione' => null,
            'nr_fatturazione' => null,
            'cap_fatturazione' => null,
            'comune_fatturazione' => null,
            'codice_destinatario' => strtoupper(Str::random(7)),
            'data_inizio_validita' => null,
            'data_fine_validita' => null,
            'cig' => null,
            'cup' => null,
            'pod' => null,
            'provenienza_mercato_libero' => null,
            'uso_non_professionale_luce' => null,
            'consumo_annuo_luce' => null,
            'potenza_contrattuale' => null,
            'livello_tensione' => null,
            'attuale_societa_luce' => null,
            'indirizzo_fornitura_luce' => null,
            'nr_fornitura_luce' => null,
            'cap_fornitura_luce' => null,
            'comune_fornitura_luce' => null,
            'pdr' => null,
            'uso_non_professionale_gas' => null,
            'attuale_societa_gas' => null,
            'profilo_consumo' => null,
            'posizione_contatore' => null,
            'consumo_annuo' => null,
            'matricola_contatore' => null,
            'indirizzo_fornitura_gas' => null,
            'nr_fornitura_gas' => null,
            'cap_fornitura_gas' => null,
            'comune_fornitura_gas' => null,
            'modalita_pagamento' => null,
            'invio_fattura' => null,
            'titolare_cc' => null,
            'codice_fiscale_titolare' => null,
            'cognome_nome_sottoscrittore' => null,
            'recapito_telefonico_titolare' => null,
            'iban' => null,
            'iban_sepa' => null,
            'tipo_documento' => null,
            'numero_documento' => null,
            'rilasciato_da' => null,
            'data_rilascio' => null,
            'data_scadenza' => null,

        ];
    }
}

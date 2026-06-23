<?php

namespace Database\Factories;

use App\Models\ProdottoEnergiaEnelConsumer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProdottoEnergiaEnelConsumer>
 */
class ProdottoEnelConsumerFactory extends Factory
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
            'residente_fornitura' => null,
            'indirizzo_fornitura' => null,
            'nr_fornitura' => null,
            'scala_fornitura' => null,
            'interno_fornitura' => null,
            'cap_fornitura' => null,
            'comune_fornitura' => null,
            'indirizzo_fatturazione' => null,
            'presso_fatturazione' => null,
            'nr_fatturazione' => null,
            'scala_fatturazione' => null,
            'interno_fatturazione' => null,
            'cap_fatturazione' => null,
            'comune_fatturazione' => null,
            'pod' => null,
            'provenienza_mercato_libero' => null,
            'consumo_annuo_luce' => null,
            'potenza_contrattuale' => null,
            'attuale_societa_luce' => null,
            'pdr' => null,
            'consumo_annuo_gas' => null,
            'attuale_societa_gas' => null,
            'riscaldamento' => null,
            'cottura_acqua_calda' => null,
            'codice_destinatario' => strtoupper(Str::random(7)),
            'indirizzo_pec' => null,
            'modalita_pagamento' => null,
            'invio_fattura' => null,
            'titolare_cc' => null,
            'codice_fiscale_titolare' => null,
            'telefono_titolare' => null,
            'iban' => null,
            'bic_swift' => null,

        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ProdottoTimWifi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProdottoTimWifi>
 */
class ProdottoTimWifiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'contratto_id' => null,
            'indirizzo_fattura' => null,
            'citta_fattura' => null,
            'cap_fattura' => null,
            'indirizzo_timcard' => null,
            'citta_timcard' => null,
            'cap_timcard' => null,
            'la_tua_linea_di_casa' => null,
            'variazione_numero' => null,
            'linea_mobile_tim' => null,
            'linea_mobile_new' => null,
            'linea_mobile_abbonamento' => null,
            'linea_mobile_prepagato' => null,
            'linea_mobile_operatore' => null,
            'linea_mobile_abbinata_offerta' => null,
            'linea_mobile_cf_piva_attuale' => null,
            'linea_mobile_numero_seriale' => null,
            'linea_mobile_trasferimento_credito' => null,
            'la_tua_offerta' => null,
            'opzione_inclusa' => null,
            'qualora' => null,
            'modem_tim' => null,
            'offerta_scelta' => null,
            'consenso_1_a' => null,
            'consenso_1_b' => null,
            'consenso_2_a' => null,
            'consenso_2_b' => null,
            'consenso_3_a' => null,
            'consenso_3_b' => null,
            'consenso_4_a' => null,
            'consenso_4_b' => null,
            'consenso_5' => null,
            'consenso_6' => null,
            'consenso_7' => null,
            'consenso_8' => null,
            'consenso_9' => null,
            'consenso_10' => null,
            'cognome_nome_debitore' => null,
            'codice_fiscale_debitore' => null,
            'iban_debitore' => null,

        ];
    }
}

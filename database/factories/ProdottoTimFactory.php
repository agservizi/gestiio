<?php

namespace Database\Factories;

use App\Models\ProdottoTim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProdottoTim>
 */
class ProdottoTimFactory extends Factory
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
            'citta_di_nascita' => null,
            'provincia_di_nascita' => null,
            'nazionalita' => null,
            'indirizzo_impianto' => null,
            'civico_impianto' => null,
            'piano_impianto' => null,
            'scala_impianto' => null,
            'interno_impianto' => null,
            'citofono_impianto' => null,
            'localita_impianto' => null,
            'indirizzo_fattura' => null,
            'civico_fattura' => null,
            'citta_fattura' => null,
            'cap_fattura' => null,
            'indirizzo_timcard' => null,
            'civico_timcard' => null,
            'citta_timcard' => null,
            'cap_timcard' => null,
            'numero_cellulare' => null,
            'recapito_alternativo' => null,
            'firmatario_nome_cognome' => null,
            'firmatario_indirizzo_completo' => null,
            'firmatario_tipo_documento' => null,
            'firmatario_rilasciato_da' => null,
            'firmatario_data_emissione' => null,
            'firmatario_data_scadenza' => null,
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

        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ContattoBrt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContattoBrt>
 */
class ContattoBrtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'agente_id' => null,
            'ragione_sociale_destinatario' => null,
            'indirizzo_destinatario' => null,
            'cap_destinatario' => null,
            'localita_destinazione' => null,
            'provincia_destinatario' => null,
            'nazione_destinazione' => null,
            'mobile_referente_consegna' => null,

        ];
    }
}

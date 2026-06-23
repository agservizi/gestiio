<?php

namespace Database\Factories;

use App\Models\SpedizioneBrt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpedizioneBrt>
 */
class SpedizioneBrtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'tipo_porto' => null,
            'ragione_sociale_destinatario' => null,
            'indirizzo_destinatario' => null,
            'cap_destinatario' => null,
            'localita_destinazione' => null,
            'nazione_destinazione' => null,
            'mobile_referente_consegna' => null,
            'numero_pacchi' => null,
            'peso_totale' => null,
            'volume_totale' => null,
            'riferimento_mittente' => null,
            'pudoId' => null,
            'nome_mittente' => null,
            'email_mittente' => null,
            'mobile_mittente' => null,
            'etichetta' => null,

        ];
    }
}

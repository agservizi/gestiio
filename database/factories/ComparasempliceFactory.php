<?php

namespace Database\Factories;

use App\Models\Comparasemplice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comparasemplice>
 */
class ComparasempliceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'data' => null,
            'agente_id' => null,
            'esito_id' => null,
            'nome' => $this->faker->firstName,
            'cognome' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail(),
            'cellulare' => null,
            'tipo_segnalazione' => null,
            'esito_finale' => null,
            'mese_pagamento' => null,
            'motivo_ko' => null,
            'pagato' => null,
            'prodotto_id' => null,
            'prodotto_type' => null,

        ];
    }
}

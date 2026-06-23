<?php

namespace Database\Factories;

use App\Models\EsitoComparasemplice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsitoComparasemplice>
 */
class EsitoComparasempliceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'nome' => $this->faker->firstName,
            'colore_hex' => null,
            'chiedi_motivo' => null,
            'notifica_mail' => null,
            'notifica_whatsapp' => null,
            'attivo' => null,
            'esito_finale' => null,

        ];
    }
}

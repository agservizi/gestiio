<?php

namespace Database\Factories;

use App\Models\CausaleTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CausaleTicket>
 */
class CausaleTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'servizio_type' => null,
            'descrizione_causale' => null,

        ];
    }
}

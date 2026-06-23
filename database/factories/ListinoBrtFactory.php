<?php

namespace Database\Factories;

use App\Models\ListinoBrt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListinoBrt>
 */
class ListinoBrtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'da_peso' => null,
            'a_peso' => null,
            'home_delivery' => null,
            'brt_fermopoint' => null,
            'al_kg' => null,

        ];
    }
}

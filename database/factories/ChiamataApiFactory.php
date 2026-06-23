<?php

namespace Database\Factories;

use App\Models\ChiamataApi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChiamataApi>
 */
class ChiamataApiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'servizio' => null,
            'url' => null,
            'method' => null,
            'request' => null,
            'response' => null,
            'status' => null,

        ];
    }
}

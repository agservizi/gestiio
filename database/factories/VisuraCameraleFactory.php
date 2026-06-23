<?php

namespace Database\Factories;

use App\Models\VisuraCamerale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisuraCameraleFactory extends Factory
{
    protected $model = VisuraCamerale::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'partita_iva' => $this->faker->numerify('##############'),
            'ragione_sociale' => $this->faker->company(),
            'data_richiesta' => now(),
            'data_ricezione' => now()->addDay(),
            'numero_visura' => $this->faker->unique()->numerify('VIS-####'),
            'stato' => $this->faker->randomElement(['richiesta', 'ricevuta', 'elaborata']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

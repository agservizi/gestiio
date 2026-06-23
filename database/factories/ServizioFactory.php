<?php

namespace Database\Factories;

use App\Models\Servizio;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServizioFactory extends Factory
{
    protected $model = Servizio::class;

    public function definition()
    {
        return [
            'nome' => $this->faker->word(),
            'descrizione' => $this->faker->sentence(),
            'tipo' => $this->faker->randomElement(['energia', 'sim', 'visura', 'caf']),
            'attivo' => $this->faker->boolean(80),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

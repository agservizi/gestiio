<?php

namespace Database\Factories;

use App\Models\Agente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgenteFactory extends Factory
{
    protected $model = Agente::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'nome' => $this->faker->firstName(),
            'cognome' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'stato' => $this->faker->randomElement(['attivo', 'inattivo']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

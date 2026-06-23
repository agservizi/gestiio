<?php

namespace Database\Factories;

use App\Models\Contratto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContrattoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contratto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'numero_contratto' => $this->faker->unique()->numerify('CTR-####'),
            'data_inizio' => now(),
            'data_fine' => now()->addYear(),
            'importo' => $this->faker->randomFloat(2, 100, 10000),
            'stato' => 'attivo',
            'note' => $this->faker->text(200),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

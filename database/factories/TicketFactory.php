<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'numero_ticket' => $this->faker->unique()->numerify('TKT-####'),
            'titolo' => $this->faker->sentence(),
            'descrizione' => $this->faker->paragraph(),
            'priorita' => $this->faker->randomElement(['bassa', 'media', 'alta']),
            'stato' => $this->faker->randomElement(['aperto', 'in_lavorazione', 'chiuso']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatThreadFactory extends Factory
{
    protected $model = ChatThread::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => null,
            'is_group' => false,
            'archived_at' => null,
        ];
    }

    public function group(?string $name = null): static
    {
        return $this->state(fn () => [
            'is_group' => true,
            'name' => $name ?? $this->faker->words(2, true),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'archived_at' => now(),
        ]);
    }
}

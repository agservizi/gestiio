<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'thread_id' => ChatThread::factory(),
            'user_id' => User::factory(),
            'messaggio' => $this->faker->sentence(),
            'priority' => 0,
        ];
    }
}

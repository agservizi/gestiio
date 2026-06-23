<?php

namespace Database\Factories;

use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatThreadFactory extends Factory
{
    protected $model = ChatThread::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

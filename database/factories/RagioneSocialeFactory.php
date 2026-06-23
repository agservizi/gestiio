<?php

namespace Database\Factories;

use App\Models\RagioneSociale;
use Illuminate\Database\Eloquent\Factories\Factory;

class RagioneSocialeFactory extends Factory
{
    protected $model = RagioneSociale::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

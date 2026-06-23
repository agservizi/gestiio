<?php

namespace Database\Factories;

use App\Models\CafPatronato;
use Illuminate\Database\Eloquent\Factories\Factory;

class CafPatronatoFactory extends Factory
{
    protected $model = CafPatronato::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

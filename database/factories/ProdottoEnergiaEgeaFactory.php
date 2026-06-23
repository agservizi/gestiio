<?php

namespace Database\Factories;

use App\Models\ProdottoEnergiaEgea;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdottoEnergiaEgeaFactory extends Factory
{
    protected $model = ProdottoEnergiaEgea::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

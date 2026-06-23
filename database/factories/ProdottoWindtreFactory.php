<?php

namespace Database\Factories;

use App\Models\ProdottoWindtre;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdottoWindtreFactory extends Factory
{
    protected $model = ProdottoWindtre::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

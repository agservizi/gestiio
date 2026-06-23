<?php

namespace Database\Factories;

use App\Models\ContrattoEnergia;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContrattoEnergiaFactory extends Factory
{
    protected $model = ContrattoEnergia::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

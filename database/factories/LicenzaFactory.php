<?php

namespace Database\Factories;

use App\Models\Licenza;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenzaFactory extends Factory
{
    protected $model = Licenza::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

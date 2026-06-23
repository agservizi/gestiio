<?php

namespace Database\Factories;

use App\Models\CartellaFiles;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartellaFilesFactory extends Factory
{
    protected $model = CartellaFiles::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

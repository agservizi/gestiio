<?php

namespace Database\Factories;

use App\Models\AttivazioneSim;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttivazioneSimFactory extends Factory
{
    protected $model = AttivazioneSim::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

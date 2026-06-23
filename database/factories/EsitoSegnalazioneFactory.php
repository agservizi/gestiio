<?php

namespace Database\Factories;

use App\Models\EsitoSegnalazione;
use Illuminate\Database\Eloquent\Factories\Factory;

class EsitoSegnalazioneFactory extends Factory
{
    protected $model = EsitoSegnalazione::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

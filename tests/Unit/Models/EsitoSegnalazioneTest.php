<?php

namespace Tests\Unit\Models;

use App\Models\EsitoSegnalazione;
use Tests\TestCase;

class EsitoSegnalazioneTest extends TestCase
{
    public function test_EsitoSegnalazione_can_be_created()
    {
        $model = EsitoSegnalazione::factory()->create();
        $this->assertInstanceOf(EsitoSegnalazione::class, $model);
        $this->assertNotNull($model->id);
    }
}

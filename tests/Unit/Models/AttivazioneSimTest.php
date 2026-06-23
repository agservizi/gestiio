<?php

namespace Tests\Unit\Models;

use App\Models\AttivazioneSim;
use Tests\TestCase;

class AttivazioneSimTest extends TestCase
{
    public function test_AttivazioneSim_can_be_created()
    {
        $model = AttivazioneSim::factory()->create();
        $this->assertInstanceOf(AttivazioneSim::class, $model);
        $this->assertNotNull($model->id);
    }
}

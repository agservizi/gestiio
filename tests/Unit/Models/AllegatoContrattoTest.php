<?php

namespace Tests\Unit\Models;

use App\Models\AllegatoContratto;
use Tests\TestCase;

class AllegatoContrattoTest extends TestCase
{
    public function test_AllegatoContratto_can_be_created()
    {
        $model = AllegatoContratto::factory()->create();
        $this->assertInstanceOf(AllegatoContratto::class, $model);
        $this->assertNotNull($model->id);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\ContrattoEnergia;
use Tests\TestCase;

class ContrattoEnergiaTest extends TestCase
{
    public function test_ContrattoEnergia_can_be_created()
    {
        $model = ContrattoEnergia::factory()->create();
        $this->assertInstanceOf(ContrattoEnergia::class, $model);
        $this->assertNotNull($model->id);
    }
}

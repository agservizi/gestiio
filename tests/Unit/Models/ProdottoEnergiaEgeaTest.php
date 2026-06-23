<?php

namespace Tests\Unit\Models;

use App\Models\ProdottoEnergiaEgea;
use Tests\TestCase;

class ProdottoEnergiaEgeaTest extends TestCase
{
    public function test_ProdottoEnergiaEgea_can_be_created()
    {
        $model = ProdottoEnergiaEgea::factory()->create();
        $this->assertInstanceOf(ProdottoEnergiaEgea::class, $model);
        $this->assertNotNull($model->id);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\RagioneSociale;
use Tests\TestCase;

class RagioneSocialeTest extends TestCase
{
    public function test_RagioneSociale_can_be_created()
    {
        $model = RagioneSociale::factory()->create();
        $this->assertInstanceOf(RagioneSociale::class, $model);
        $this->assertNotNull($model->id);
    }
}

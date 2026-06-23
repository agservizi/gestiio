<?php

namespace Tests\Unit\Models;

use App\Models\CafPatronato;
use Tests\TestCase;

class CafPatronatoTest extends TestCase
{
    public function test_CafPatronato_can_be_created()
    {
        $model = CafPatronato::factory()->create();
        $this->assertInstanceOf(CafPatronato::class, $model);
        $this->assertNotNull($model->id);
    }
}

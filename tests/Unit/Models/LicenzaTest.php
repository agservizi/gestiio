<?php

namespace Tests\Unit\Models;

use App\Models\Licenza;
use Tests\TestCase;

class LicenzaTest extends TestCase
{
    public function test_Licenza_can_be_created()
    {
        $model = Licenza::factory()->create();
        $this->assertInstanceOf(Licenza::class, $model);
        $this->assertNotNull($model->id);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\ProdottoWindtre;
use Tests\TestCase;

class ProdottoWindtreTest extends TestCase
{
    public function test_ProdottoWindtre_can_be_created()
    {
        $model = ProdottoWindtre::factory()->create();
        $this->assertInstanceOf(ProdottoWindtre::class, $model);
        $this->assertNotNull($model->id);
    }
}

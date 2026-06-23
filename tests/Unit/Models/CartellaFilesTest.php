<?php

namespace Tests\Unit\Models;

use App\Models\CartellaFiles;
use Tests\TestCase;

class CartellaFilesTest extends TestCase
{
    public function test_CartellaFiles_can_be_created()
    {
        $model = CartellaFiles::factory()->create();
        $this->assertInstanceOf(CartellaFiles::class, $model);
        $this->assertNotNull($model->id);
    }
}

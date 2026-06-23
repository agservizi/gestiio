<?php

namespace Tests\Unit\Models;

use App\Models\Persona;
use Tests\TestCase;

class PersonaTest extends TestCase
{
    public function test_Persona_can_be_created()
    {
        $model = Persona::factory()->create();
        $this->assertInstanceOf(Persona::class, $model);
        $this->assertNotNull($model->id);
    }
}

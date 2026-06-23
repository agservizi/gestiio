<?php

namespace Tests\Unit\Models;

use App\Models\Servizio;
use Tests\TestCase;

class ServizioTest extends TestCase
{
    public function test_servizio_can_be_created()
    {
        $servizio = Servizio::factory()->create();
        $this->assertInstanceOf(Servizio::class, $servizio);
    }

    public function test_servizio_has_required_fields()
    {
        $servizio = Servizio::factory()->create([
            'nome' => 'Energia',
            'tipo' => 'energia',
        ]);
        $this->assertEquals('Energia', $servizio->nome);
        $this->assertEquals('energia', $servizio->tipo);
    }

    public function test_servizio_can_be_active_or_inactive()
    {
        $active = Servizio::factory()->create(['attivo' => true]);
        $inactive = Servizio::factory()->create(['attivo' => false]);
        $this->assertTrue($active->attivo);
        $this->assertFalse($inactive->attivo);
    }
}

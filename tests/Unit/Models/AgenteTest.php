<?php

namespace Tests\Unit\Models;

use App\Models\Agente;
use App\Models\User;
use Tests\TestCase;

class AgenteTest extends TestCase
{
    public function test_agente_can_be_created()
    {
        $agente = Agente::factory()->create();
        $this->assertInstanceOf(Agente::class, $agente);
        $this->assertNotNull($agente->id);
    }

    public function test_agente_belongs_to_user()
    {
        $user = User::factory()->create();
        $agente = Agente::factory()->create(['user_id' => $user->id]);
        $this->assertEquals($user->id, $agente->user->id);
    }

    public function test_agente_has_valid_status()
    {
        $agente = Agente::factory()->create(['stato' => 'attivo']);
        $this->assertEquals('attivo', $agente->stato);
    }
}

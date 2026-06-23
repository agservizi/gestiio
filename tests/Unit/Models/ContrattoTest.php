<?php

namespace Tests\Unit\Models;

use App\Models\Contratto;
use App\Models\User;
use Tests\TestCase;

class ContrattoTest extends TestCase
{
    /**
     * Test that a contratto can be created with factory.
     *
     * @return void
     */
    public function test_contratto_can_be_created()
    {
        $contratto = Contratto::factory()->create();

        $this->assertInstanceOf(Contratto::class, $contratto);
        $this->assertNotNull($contratto->id);
        $this->assertNotNull($contratto->numero_contratto);
    }

    /**
     * Test contratto belongs to user.
     *
     * @return void
     */
    public function test_contratto_belongs_to_user()
    {
        $user = User::factory()->create();
        $contratto = Contratto::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $contratto->user);
        $this->assertEquals($user->id, $contratto->user->id);
    }

    /**
     * Test that contratto has correct attributes.
     *
     * @return void
     */
    public function test_contratto_has_correct_attributes()
    {
        $attributes = [
            'numero_contratto' => 'CTR-2024-001',
            'stato' => 'attivo',
            'importo' => 5000.00,
        ];

        $contratto = Contratto::factory()->create($attributes);

        $this->assertEquals('CTR-2024-001', $contratto->numero_contratto);
        $this->assertEquals('attivo', $contratto->stato);
        $this->assertEquals(5000.00, $contratto->importo);
    }
}

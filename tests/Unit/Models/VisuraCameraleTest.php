<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\VisuraCamerale;
use Tests\TestCase;

class VisuraCameraleTest extends TestCase
{
    public function test_visura_can_be_created()
    {
        $visura = VisuraCamerale::factory()->create();
        $this->assertInstanceOf(VisuraCamerale::class, $visura);
    }

    public function test_visura_belongs_to_user()
    {
        $user = User::factory()->create();
        $visura = VisuraCamerale::factory()->create(['user_id' => $user->id]);
        $this->assertEquals($user->id, $visura->user->id);
    }

    public function test_visura_has_valid_states()
    {
        $states = ['richiesta', 'ricevuta', 'elaborata'];
        foreach ($states as $state) {
            $visura = VisuraCamerale::factory()->create(['stato' => $state]);
            $this->assertEquals($state, $visura->stato);
        }
    }
}

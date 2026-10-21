<?php

namespace Tests\Unit;

use App\Models\GaraSoglia;
use Tests\TestCase;

class GaraSogliaMatchTest extends TestCase
{
    public function test_match_open_ended_range(): void
    {
        $s = new GaraSoglia(['da_valore' => 5, 'a_valore' => null, 'premio' => 50]);

        $this->assertFalse($s->matchValore(4));
        $this->assertTrue($s->matchValore(5));
        $this->assertTrue($s->matchValore(99));
    }

    public function test_match_closed_range(): void
    {
        $s = new GaraSoglia(['da_valore' => 3, 'a_valore' => 5, 'premio' => 20]);

        $this->assertFalse($s->matchValore(2));
        $this->assertTrue($s->matchValore(3));
        $this->assertTrue($s->matchValore(5));
        $this->assertFalse($s->matchValore(6));
    }
}

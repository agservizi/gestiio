<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\User;
use Tests\TestCase;

class TicketTest extends TestCase
{
    /**
     * Test that a ticket can be created with factory.
     *
     * @return void
     */
    public function test_ticket_can_be_created()
    {
        $ticket = Ticket::factory()->create();

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNotNull($ticket->id);
        $this->assertNotNull($ticket->numero_ticket);
    }

    /**
     * Test ticket belongs to user.
     *
     * @return void
     */
    public function test_ticket_belongs_to_user()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $ticket->user);
        $this->assertEquals($user->id, $ticket->user->id);
    }

    /**
     * Test that ticket has valid priority levels.
     *
     * @return void
     */
    public function test_ticket_has_valid_priority_levels()
    {
        $priorities = ['bassa', 'media', 'alta'];

        foreach ($priorities as $priority) {
            $ticket = Ticket::factory()->create(['priorita' => $priority]);
            $this->assertEquals($priority, $ticket->priorita);
        }
    }

    /**
     * Test that ticket has valid status.
     *
     * @return void
     */
    public function test_ticket_has_valid_status()
    {
        $statuses = ['aperto', 'in_lavorazione', 'chiuso'];

        foreach ($statuses as $status) {
            $ticket = Ticket::factory()->create(['stato' => $status]);
            $this->assertEquals($status, $ticket->stato);
        }
    }
}

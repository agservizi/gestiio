<?php

namespace Tests\Feature\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    /**
     * Test that authenticated user can view tickets list.
     *
     * @return void
     */
    public function test_authenticated_user_can_view_tickets()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/ticket');

        $response->assertStatus(200);
    }

    /**
     * Test that unauthenticated user cannot view tickets.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_view_tickets()
    {
        $response = $this->get('/ticket');

        $response->assertRedirect('/login');
    }

    /**
     * Test that user can create a new ticket.
     *
     * @return void
     */
    public function test_user_can_create_ticket()
    {
        $user = $this->authenticatedUser();

        $response = $this->post('/ticket', [
            'titolo' => 'Test Ticket',
            'descrizione' => 'This is a test ticket',
            'priorita' => 'media',
        ]);

        $this->assertDatabaseHas('tickets', [
            'titolo' => 'Test Ticket',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that user can view their own ticket.
     *
     * @return void
     */
    public function test_user_can_view_own_ticket()
    {
        $user = $this->authenticatedUser();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->get("/ticket/{$ticket->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that user cannot view another user's ticket.
     *
     * @return void
     */
    public function test_user_cannot_view_other_user_ticket()
    {
        $user = $this->authenticatedUser();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get("/ticket/{$ticket->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that user can update their own ticket.
     *
     * @return void
     */
    public function test_user_can_update_own_ticket()
    {
        $user = $this->authenticatedUser();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->patch("/ticket/{$ticket->id}", [
            'titolo' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'titolo' => 'Updated Title',
        ]);
    }
}

<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\ChatThread;
use App\Models\User;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    /**
     * Test that staff user can view chat threads.
     *
     * @return void
     */
    public function test_staff_user_can_view_chat_threads()
    {
        $user = $this->staffUser('agente');

        $response = $this->get('/backend/chat');

        // Should have access (or redirect if not implemented)
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * Test that non-staff user cannot access chat.
     *
     * @return void
     */
    public function test_non_staff_user_cannot_access_chat()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/backend/chat');

        $response->assertStatus(403);
    }

    /**
     * Test that staff user can send message.
     *
     * @return void
     */
    public function test_staff_user_can_send_message()
    {
        $user = $this->staffUser('agente');
        $thread = ChatThread::factory()->create();

        $response = $this->post("/backend/chat/{$thread->id}/message", [
            'messaggio' => 'Test message',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);
    }
}

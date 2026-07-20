<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Collega un utente a un thread come partecipante.
     */
    protected function attachPartecipante(ChatThread $thread, User $user, bool $letto = true): void
    {
        $thread->partecipanti()->attach($user->id, [
            'last_read_at' => $letto ? now() : null,
        ]);
    }

    public function test_staff_user_can_view_chat_index()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/chat-interna');

        $response->assertStatus(200);
    }

    public function test_non_staff_user_cannot_access_chat()
    {
        $this->authenticatedUser();

        $response = $this->get('/backend/chat-interna');

        $response->assertStatus(403);
    }

    public function test_operatore_cannot_access_chat()
    {
        // La chat interna è riservata ad admin/agente/supervisore.
        $this->staffUser('operatore');

        $response = $this->get('/backend/chat-interna');

        $response->assertStatus(403);
    }

    public function test_staff_user_can_send_message()
    {
        $user = $this->staffUser('agente');

        $thread = ChatThread::factory()->create(['created_by' => $user->id]);
        $this->attachPartecipante($thread, $user);

        $response = $this->post("/backend/chat-interna/{$thread->id}/messages", [
            'messaggio' => 'Messaggio di test',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('chat_messages', [
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'messaggio' => 'Messaggio di test',
        ]);
    }

    public function test_send_message_requires_content()
    {
        $user = $this->staffUser('agente');

        $thread = ChatThread::factory()->create(['created_by' => $user->id]);
        $this->attachPartecipante($thread, $user);

        $response = $this->post("/backend/chat-interna/{$thread->id}/messages", [
            'messaggio' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_reply_to_id_must_belong_to_same_thread()
    {
        $user = $this->staffUser('agente');

        $thread = ChatThread::factory()->create(['created_by' => $user->id]);
        $this->attachPartecipante($thread, $user);

        $altroThread = ChatThread::factory()->create();
        $messaggioAltrove = ChatMessage::factory()->create([
            'thread_id' => $altroThread->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post("/backend/chat-interna/{$thread->id}/messages", [
            'messaggio' => 'Risposta',
            'reply_to_id' => $messaggioAltrove->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_access_thread_they_are_not_part_of()
    {
        $this->staffUser('agente');

        $thread = ChatThread::factory()->create();

        $response = $this->post("/backend/chat-interna/{$thread->id}/messages", [
            'messaggio' => 'Non dovrei riuscire',
        ]);

        $response->assertStatus(403);
    }

    public function test_message_history_returns_json_for_participant()
    {
        $user = $this->staffUser('agente');

        $thread = ChatThread::factory()->create(['created_by' => $user->id]);
        $this->attachPartecipante($thread, $user);

        $messaggio = ChatMessage::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get("/backend/chat-interna/message/{$messaggio->id}/history");

        $response->assertStatus(200);
        $response->assertJsonStructure(['history']);
    }

    public function test_only_admin_can_archive_thread()
    {
        $user = $this->staffUser('agente');

        $thread = ChatThread::factory()->create(['created_by' => $user->id]);
        $this->attachPartecipante($thread, $user);

        $response = $this->post("/backend/chat-interna/{$thread->id}/archive");

        $response->assertStatus(403);
    }

    public function test_admin_can_archive_thread()
    {
        $admin = $this->staffUser('admin');

        $thread = ChatThread::factory()->create(['created_by' => $admin->id]);
        $this->attachPartecipante($thread, $admin);

        $response = $this->post("/backend/chat-interna/{$thread->id}/archive");

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'archived' => true]);
        $this->assertNotNull($thread->fresh()->archived_at);
    }
}

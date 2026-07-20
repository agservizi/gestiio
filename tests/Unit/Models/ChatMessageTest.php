<?php

namespace Tests\Unit\Models;

use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ChatMessage_can_be_created()
    {
        $model = ChatMessage::factory()->create();
        $this->assertInstanceOf(ChatMessage::class, $model);
        $this->assertNotNull($model->id);
    }
}

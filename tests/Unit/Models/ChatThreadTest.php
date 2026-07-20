<?php

namespace Tests\Unit\Models;

use App\Models\ChatThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_ChatThread_can_be_created()
    {
        $model = ChatThread::factory()->create();
        $this->assertInstanceOf(ChatThread::class, $model);
        $this->assertNotNull($model->id);
    }
}

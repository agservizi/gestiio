<?php

namespace Tests\Unit\Models;

use App\Models\ChatThread;
use Tests\TestCase;

class ChatThreadTest extends TestCase
{
    public function test_ChatThread_can_be_created()
    {
        $model = ChatThread::factory()->create();
        $this->assertInstanceOf(ChatThread::class, $model);
        $this->assertNotNull($model->id);
    }
}

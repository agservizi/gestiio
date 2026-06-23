<?php

namespace Tests\Unit\Models;

use App\Models\FileAuditLog;
use Tests\TestCase;

class FileAuditLogTest extends TestCase
{
    public function test_FileAuditLog_can_be_created()
    {
        $model = FileAuditLog::factory()->create();
        $this->assertInstanceOf(FileAuditLog::class, $model);
        $this->assertNotNull($model->id);
    }
}

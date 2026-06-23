<?php

namespace Database\Factories;

use App\Models\FileAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileAuditLogFactory extends Factory
{
    protected $model = FileAuditLog::class;

    public function definition()
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];
}

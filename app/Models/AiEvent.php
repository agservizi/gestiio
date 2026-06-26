<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEvent extends Model
{
    protected $table = 'ai_events';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function suggestion()
    {
        return $this->hasOne(AiSuggestion::class, 'ai_event_id');
    }
}

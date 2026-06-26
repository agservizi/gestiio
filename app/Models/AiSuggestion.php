<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    protected $table = 'ai_suggestions';

    protected $guarded = [];

    protected $casts = [
        'actions' => 'array',
        'metadata' => 'array',
        'seen_at' => 'datetime',
        'accepted_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(AiEvent::class, 'ai_event_id');
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['alta', 'critica'], true);
    }
}

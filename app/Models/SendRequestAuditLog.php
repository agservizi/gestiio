<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'send_request_id',
        'user_id',
        'action',
        'ip',
        'user_agent',
        'before',
        'after',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

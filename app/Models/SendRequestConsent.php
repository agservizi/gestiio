<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestConsent extends Model
{
    protected $fillable = [
        'send_request_id',
        'consent_type',
        'privacy_version',
        'accepted',
        'accepted_by',
        'accepted_at',
        'metadata',
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }
}

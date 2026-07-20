<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestChecklistItem extends Model
{
    protected $fillable = [
        'send_request_id',
        'code',
        'label',
        'required',
        'completed',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'required' => 'boolean',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }
}

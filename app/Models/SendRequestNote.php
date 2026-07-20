<?php

namespace App\Models;

use App\Enums\SendNoteVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendRequestNote extends Model
{
    protected $fillable = [
        'send_request_id',
        'author_id',
        'note_type',
        'visibility',
        'body',
    ];

    protected $casts = [
        'visibility' => SendNoteVisibility::class,
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SendRequest::class, 'send_request_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

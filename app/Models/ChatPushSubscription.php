<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatPushSubscription extends Model
{
    use HasFactory;

    protected $table = 'chat_push_subscriptions';

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'is_enabled',
        'last_used_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

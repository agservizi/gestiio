<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'thread_id',
        'user_id',
        'messaggio',
        'reply_to_id',
        'delivered_at',
        'edited_at',
        'deleted_at',
        'priority',
        'forwarded_from_id',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function mittente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function allegati()
    {
        return $this->hasMany(ChatMessageAttachment::class, 'message_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function reazioni()
    {
        return $this->hasMany(ChatMessageReaction::class, 'message_id');
    }

    public function inoltratoDa()
    {
        return $this->belongsTo(self::class, 'forwarded_from_id');
    }

    public function pin()
    {
        return $this->hasMany(ChatMessagePin::class, 'message_id');
    }

    public function preferiti()
    {
        return $this->hasMany(ChatMessageFavorite::class, 'message_id');
    }

    public function audit()
    {
        return $this->hasMany(ChatMessageAudit::class, 'message_id');
    }

    public function menzioni()
    {
        return $this->hasMany(ChatMessageMention::class, 'message_id');
    }
}

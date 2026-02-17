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
}

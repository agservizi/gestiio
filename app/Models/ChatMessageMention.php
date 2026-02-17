<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageMention extends Model
{
    use HasFactory;

    protected $table = 'chat_message_mentions';

    protected $fillable = [
        'message_id',
        'mentioned_user_id',
    ];

    public function messaggio()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function menzionato()
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}

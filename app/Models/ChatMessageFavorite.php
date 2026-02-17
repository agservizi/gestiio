<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageFavorite extends Model
{
    use HasFactory;

    protected $table = 'chat_message_favorites';

    protected $fillable = [
        'message_id',
        'user_id',
    ];

    public function messaggio()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

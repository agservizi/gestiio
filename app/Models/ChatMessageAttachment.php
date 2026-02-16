<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessageAttachment extends Model
{
    use HasFactory;

    protected $table = 'chat_message_attachments';

    protected $fillable = [
        'message_id',
        'filename_originale',
        'path_filename',
        'mime_type',
        'dimensione_file',
    ];

    public function messaggio()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }
}

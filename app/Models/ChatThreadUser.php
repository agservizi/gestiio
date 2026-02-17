<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatThreadUser extends Model
{
    use HasFactory;

    protected $table = 'chat_thread_users';

    protected $fillable = [
        'thread_id',
        'user_id',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'muted_until' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function conteggioNonLetti(int $userId): int
    {
        return self::query()
            ->where('chat_thread_users.user_id', $userId)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('chat_messages')
                    ->whereColumn('chat_messages.thread_id', 'chat_thread_users.thread_id')
                    ->whereColumn('chat_messages.user_id', '<>', 'chat_thread_users.user_id')
                    ->whereRaw("chat_messages.created_at > COALESCE(chat_thread_users.last_read_at, '1970-01-01 00:00:00')");
            })
            ->count();
    }
}

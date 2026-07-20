<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    use HasFactory;

    protected $table = 'chat_threads';

    protected $fillable = [
        'created_by',
        'name',
        'is_group',
        'archived_at',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function creatore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function partecipanti()
    {
        return $this->belongsToMany(User::class, 'chat_thread_users', 'thread_id', 'user_id')
            ->withPivot(['last_read_at'])
            ->withTimestamps();
    }

    public function partecipazioni()
    {
        return $this->hasMany(ChatThreadUser::class, 'thread_id');
    }

    public function messaggi()
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function ultimoMessaggio()
    {
        return $this->hasOne(ChatMessage::class, 'thread_id')->latestOfMany();
    }

    public function messaggiPinnati()
    {
        return $this->hasMany(ChatMessage::class, 'thread_id')
            ->whereHas('pin')
            ->latest('id');
    }

    /**
     * Nome da mostrare per il thread: per i gruppi usa il campo `name`,
     * per le conversazioni 1:1 il chiamante userà l'altro partecipante.
     */
    public function nomeVisualizzato(): ?string
    {
        if ($this->is_group) {
            return $this->name ?: 'Gruppo';
        }

        return $this->name;
    }
}

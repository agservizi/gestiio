<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatQuickTemplate extends Model
{
    use HasFactory;

    protected $table = 'chat_quick_templates';

    protected $fillable = [
        'user_id',
        'titolo',
        'contenuto',
        'is_global',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

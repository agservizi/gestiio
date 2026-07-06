<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAttivita extends Model
{
    protected $table = 'registro_attivita';

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'route_name',
        'controller_action',
        'status_code',
        'duration_ms',
        'ip',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

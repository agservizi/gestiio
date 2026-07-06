<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'setting_name',
        'old_value',
        'new_value',
        'action',
        'ip',
        'user_agent',
    ];

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

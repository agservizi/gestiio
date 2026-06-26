<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAction extends Model
{
    protected $table = 'ai_actions';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}

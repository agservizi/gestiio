<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{

    protected $table = 'sms';

    public const NOME_SINGOLARE = "sms";
    public const NOME_PLURALE = "sms";

    protected $casts = [
        'recipients' => 'array',
        'response' => 'array',
    ];

}

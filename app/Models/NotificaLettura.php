<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificaLettura extends Model
{
    use HasFactory;
    protected $table='notifiche_letture';

    protected $fillable=[
        'notifica_id','user_id'
    ];

}

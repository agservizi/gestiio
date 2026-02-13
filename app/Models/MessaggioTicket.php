<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessaggioTicket extends Model
{
    use HasFactory;

    protected $table = 'tickets_messaggi';

    protected $casts = [
        'letto' => 'datetime'
    ];

    protected $fillable = [
        'letto'
    ];


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function utente()
    {
        return $this->hasOne(User::class, 'id', 'user_id',);
    }

    public function allegati()
    {
        return $this->hasMany(AllegatoMessaggioTicket::class, 'messaggio_id', 'id');
    }


}

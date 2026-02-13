<?php

namespace App\Models;

use App\Http\MieClassiCache\CacheConteggioTicketsDaLeggere;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LetturaTicket extends Model
{
    protected $table = 'tickets_letture';

    public $timestamps = false;

    protected $fillable = ['messaggio_letto', 'data_lettura'];


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {


        self::saved(function ($model) {
            CacheConteggioTicketsDaLeggere::forget($model->user_id);
        });

    }
}

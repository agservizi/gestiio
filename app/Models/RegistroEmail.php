<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int|null $user_id
 * @property string $email
 * @property string $ip
 * @property int $riuscito
 * @property bool remember
 */
class RegistroEmail extends Model
{
    //
    protected $table = 'registro_email';
    public $timestamps = false;

    protected $dates = ['data'];




    /***************************************************
     * Accessor and mutators
     ***************************************************/


    public function setBodyAttribute($value)
    {
        //Non usato perchè per aggiungere uso DB::table
        $this->attributes['body'] = base64_encode(gzcompress($value, 9));
    }


    public function getBodyAttribute()
    {
        return gzuncompress(base64_decode($this->attributes['body']));
    }


}

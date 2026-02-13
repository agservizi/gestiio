<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Malhal\Geographical\Geographical;

class Comune extends Model
{


    //https://github.com/malhal/Laravel-Geographical
    //use Geographical;




    public $timestamps = false;
    protected $table = 'elenco_comuni';

    protected static $kilometers=true;


    /***************************************************
     * Relazioni
     ***************************************************/

    public function provincia()
    {
        return $this->hasOne('\App\Models\Provincia', 'id', 'provincia_id')->withDefault();
    }


    /***************************************************
     * Scopes
     ***************************************************/

    public function scopeAttivi($query)
    {
        return $query->where('soppresso', 0);
    }

    public function scopeSoppressi($query)
    {
        return $query->where('soppresso', 1);
    }


    /***************************************************
     * Accessor and mutators
     ***************************************************/

    public function getComuneProvinciaAttribute()
    {
        if (array_key_exists('comune', $this->attributes)) {
            return $this->attributes['comune'] . ' (' . $this->attributes['targa'] . ')';

        } else {
            return '';
        }

    }

    public function comuneConTarga()
    {
        return $this->comune . ' (' . $this->targa . ')';
    }


    public static function selected($id)
    {
        $record = self::find($id);
        if ($record) {
            return "<option value='$id' selected>{$record->comune} ({$record->targa})</option>";
        }
    }


}

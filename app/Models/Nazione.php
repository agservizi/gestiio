<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Nazione extends Model
{
    protected $table = 'elenco_nazioni';

    public $incrementing = false;
    public $primaryKey = 'alpha2';
    public $timestamps = false;


    public static function selected($id)
    {
        $nazione = self::find($id);
        if ($nazione) {
            return "<option value='$id' selected>{$nazione->langIT}</option>";
        }
    }

}

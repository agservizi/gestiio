<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;


class Provincia extends Model
{
    //
    public $timestamps = false;
    protected $table = 'elenco_province';

    //https://www.codetutorial.io/geo-spatial-mysql-laravel-5/
    //protected $geofields = array('location');


    public static function selected($id)
    {
        $record = self::find($id);
        if ($record) {
            return "<option value='$id' selected>{$record->provincia}</option>";
        }
    }



    public static function selectedString($id)
    {
        $record = self::firstWhere('sigla_automobilistica',$id);
        if ($record) {
            return "<option value='$id' selected>{$record->provincia}</option>";
        }
    }



    public function comuni(){
        return $this->hasMany(Comune::class,'provincia_id','id');
    }




    public function setLocationAttribute($value) {
        $this->attributes['location'] = DB::raw("POINT($value)");
    }

    public function getLocationAttribute($value){

        $loc =  substr($value, 6);
        $loc = preg_replace('/[ ,]+/', ',', $loc, 1);

        return substr($loc,0,-1);
    }




}

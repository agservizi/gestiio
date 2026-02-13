<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NazioneEuropaBrt extends Model
{
    protected $table = 'brt_nazioni_europa';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nome_nazione',
        'gruppo'
    ];

    public static function selected($id)
    {
        $nazione = self::find($id);
        if ($nazione) {
            return "<option value='$id' selected>{$nazione->langIT}</option>";
        }
    }
}

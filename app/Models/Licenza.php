<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class Licenza extends Model
{
    //
    protected $table = 'licenze';
    protected $dates = ['data_acquisto'];


    public static function store($nome, $venditore, $numero_licenza, $data_acquisto_giorno,$data_acquisto_mese,$data_acquisto_anno, $url)
    {

        $lic = new Licenza();
        $lic->nome = $nome;
        $lic->venditore = $venditore;
        $lic->numero_licenza = $numero_licenza;
        $lic->data_acquisto = Carbon::create($data_acquisto_anno, $data_acquisto_mese, $data_acquisto_giorno);
        $lic->url = $url;
        $lic->save();

    }

}

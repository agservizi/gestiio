<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabMotivoKo extends Model
{
    use HasFactory;

    protected $table = 'tab_motivi_ko';
    protected $fillable = ['nome', 'tipo'];


    public function scopePerModal($query, $tipo)
    {

        $query->orderBy('conteggio')->limit(5)->where('tipo', $tipo)->where('conteggio', '>', 1);
    }
}

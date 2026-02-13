<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IntestazioneFatturaProforma extends Model
{
    protected $table='fatture_proforma_intestazioni';

    public function agente(): HasOne
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}

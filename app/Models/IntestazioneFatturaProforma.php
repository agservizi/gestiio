<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntestazioneFatturaProforma extends Model
{
    protected $table = 'fatture_proforma_intestazioni';

    public function agente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

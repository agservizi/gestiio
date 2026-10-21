<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaraSoglia extends Model
{
    protected $table = 'gara_soglie';

    protected $guarded = [];

    protected $casts = [
        'da_valore' => 'integer',
        'a_valore' => 'integer',
        'premio' => 'float',
    ];

    public function gara(): BelongsTo
    {
        return $this->belongsTo(Gara::class, 'gara_id');
    }

    public function matchValore(int $valore): bool
    {
        if ($valore < (int) $this->da_valore) {
            return false;
        }
        if ($this->a_valore === null) {
            return true;
        }

        return $valore <= (int) $this->a_valore;
    }
}

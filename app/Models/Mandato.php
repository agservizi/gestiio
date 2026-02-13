<?php

namespace App\Models;

use App\Http\MieClassiCache\CacheGestoriDashboard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mandato extends Model
{
    protected $table = 'mandati';

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {


        self::saved(function ($model) {
            CacheGestoriDashboard::forget($model->agente_id);
        });


    }


    public function gestore(): HasOne
    {
        return $this->hasOne(Gestore::class, 'id', 'gestore_id')->select(['id','nome','logo']);
    }

}

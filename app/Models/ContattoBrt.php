<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class ContattoBrt extends Model
{
    use HasFactory;

    protected $table = "brt_contatti";

    public const NOME_SINGOLARE = "preferito";
    public const NOME_PLURALE = "preferiti";

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            $builder->where('agente_id', Auth::id());
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */
    public function provincia(): HasOne
    {
        return $this->hasOne(Provincia::class, 'sigla_automobilistica', 'provincia_destinatario');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}

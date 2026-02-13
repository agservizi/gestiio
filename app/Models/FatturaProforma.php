<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use function App\applicaIva;

class FatturaProforma extends Model
{
    protected $table = 'fatture_proforma';

    public const NOME_SINGOLARE = "fattura proforma";
    public const NOME_PLURALE = "fatture proforma";


    protected $casts = [
        'data' => 'date',
    ];


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            /** @var User|null $user */
            $user = Auth::user();
            if ($user && $user->hasPermissionTo('agente')) {
                $builder->whereHas('intestazione.agente', function ($q) {
                    $q->where('id', Auth::id());
                });
            }
        });

        static::saving(function (FatturaProforma $model) {

            if ($model->totale_imponibile) {
                $model->totale_con_iva = applicaIva($model->totale_imponibile, $model->aliquota_iva);
            }
        });


    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function righe()
    {
        return $this->hasMany(RigaFatturaProforma::class, 'fattura_proforma_id', 'id');
    }

    public function intestazione()
    {
        return $this->hasOne(IntestazioneFatturaProforma::class, 'id', 'intestazione_id');
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

<?php

namespace App\Models;

use App\Enums\TipiPortafoglioEnum;
use App\Notifications\NotificaAdminMovimentoPortafoglio;
use App\Notifications\NotificaAgenteCambioEsitoContratto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MovimentoPortafoglio extends Model
{

    protected $table = "movimenti_portafoglio";

    public const NOME_SINGOLARE = "portafoglio";
    public const NOME_PLURALE = "portafoglii";


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        self::saving(function (MovimentoPortafoglio $model) {
            $agente = Agente::firstWhere('user_id', $model->agente_id);
            switch ($model->portafoglio) {
                case TipiPortafoglioEnum::SERVIZI->value:
                    $model->importo_prima = $agente->portafoglio_servizi;
                    $agente->portafoglio_servizi = $agente->portafoglio_servizi + $model->importo;
                    $model->importo_dopo = $agente->portafoglio_servizi;
                    break;

                case TipiPortafoglioEnum::SPEDIZIONI->value:
                    $model->importo_prima = $agente->portafoglio_spedizioni;
                    $agente->portafoglio_spedizioni = $agente->portafoglio_spedizioni + $model->importo;
                    $model->importo_dopo = $agente->portafoglio_spedizioni;
                    break;
            }

            $agente->save();


            dispatch(function () use ($model) {
                $userNotifica = User::find(2);
                $userNotifica->notify(new NotificaAdminMovimentoPortafoglio($model));
            })->afterResponse();

        });


        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            $builder->where('agente_id', Auth::id());
        });


    }
    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

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

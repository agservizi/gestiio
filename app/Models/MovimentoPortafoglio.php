<?php

namespace App\Models;

use App\Enums\TipiPortafoglioEnum;
use App\Notifications\NotificaAdminMovimentoPortafoglio;
use App\Notifications\NotificaSogliaMinimaPortafoglio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MovimentoPortafoglio extends Model
{
    protected $table = 'movimenti_portafoglio';

    public const NOME_SINGOLARE = 'portafoglio';

    public const NOME_PLURALE = 'portafoglii';

    public const SOGLIA_MINIMA_PORTAFOGLIO = 5.0;

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

                case TipiPortafoglioEnum::VISURE->value:
                    $model->importo_prima = (float) ($agente->portafoglio_visure ?? 0);
                    $agente->portafoglio_visure = (float) ($agente->portafoglio_visure ?? 0) + $model->importo;
                    $model->importo_dopo = $agente->portafoglio_visure;
                    break;
            }

            $agente->save();

            dispatch(function () use ($model) {
                $userNotifica = User::find(2);
                $userNotifica->notify(new NotificaAdminMovimentoPortafoglio($model));
            })->afterResponse();

            if ($model->importo_prima >= self::SOGLIA_MINIMA_PORTAFOGLIO && $model->importo_dopo < self::SOGLIA_MINIMA_PORTAFOGLIO) {
                dispatch(function () use ($model) {
                    self::avvisaSogliaMinima($model);
                })->afterResponse();
            }

        });

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente') && ! Auth::user()->hasPermissionTo('admin')) {
                $builder->where('agente_id', Auth::id());
            }
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

    protected static function avvisaSogliaMinima(MovimentoPortafoglio $model): void
    {
        $tipo = TipiPortafoglioEnum::tryFrom($model->portafoglio);
        if (! $tipo) {
            return;
        }

        $utenteAgente = User::find($model->agente_id);
        $nominativo = $utenteAgente?->nominativo() ?? 'Agente #'.$model->agente_id;
        $saldo = (float) $model->importo_dopo;
        $soglia = self::SOGLIA_MINIMA_PORTAFOGLIO;
        $titolo = 'Portafoglio '.$tipo->testo().' sotto soglia minima';

        Notifica::notificaAdAdmin(
            $titolo,
            'Il portafoglio <span class="fw-bold">'.$tipo->testo().'</span> di <span class="fw-bold">'.$nominativo.'</span> è sceso a '.importo($saldo, true).'. Va ricaricato per non bloccare le lavorazioni.',
            'error'
        );

        $userAdmin = User::find(2);
        $userAdmin?->notify(new NotificaSogliaMinimaPortafoglio($tipo, $saldo, $soglia, $nominativo, false));

        if ($utenteAgente) {
            Notifica::notificaAdAgente(
                $utenteAgente,
                $titolo,
                'Il tuo portafoglio <span class="fw-bold">'.$tipo->testo().'</span> è sceso a '.importo($saldo, true).'. Ricarica il credito per continuare a lavorare senza interruzioni.',
                'error'
            );
            $utenteAgente->notify(new NotificaSogliaMinimaPortafoglio($tipo, $saldo, $soglia, $nominativo, true));
        }
    }
}

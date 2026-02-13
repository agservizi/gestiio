<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Segnalazione extends Model
{
    use HasFactory;

    protected $table = "segnalazioni";

    public const NOME_SINGOLARE = "segnalazione";
    public const NOME_PLURALE = "segnalazioni";

    public const SETTORI = array(
        'Agenzia di viaggio, tour operator' => 'Agenzia di viaggio, tour operator',
        'Alloggio e ristorazione' => 'Alloggio e ristorazione',
        'Manifattura' => 'Manifattura',
        'Attività professionali, scientifiche e tecniche' => 'Attività professionali, scientifiche e tecniche',
        'Attività artistiche, sportive e di intrattenimento' => 'Attività artistiche, sportive e di intrattenimento',
        'Consulenza' => 'Consulenza',
        'Costruzioni' => 'Costruzioni',
        'Farmacia' => 'Farmacia',
        'Finanza, Assicurazioni, Immobili' => 'Finanza, Assicurazioni, Immobili',
        'Laboratori' => 'Laboratori',
        'Produzione elettronica' => 'Produzione elettronica',
        'Servizi di informazione e comunicazione' => 'Servizi di informazione e comunicazione',
        'Forniture mediche e dentistiche' => 'Forniture mediche e dentistiche',
        'Studio notarile' => 'Studio notarile',
        'Studio odontoiatrico' => 'Studio odontoiatrico',
        'Studio veterinario' => 'Studio veterinario',
        'Trasporto e magazzinaggio' => 'Trasporto e magazzinaggio',
        'Vendita al dettaglio o all\'ingrosso' => 'Vendita al dettaglio o all\'ingrosso',
        'Altro' => 'Altro',
    );

    public const NATURE_GIURIDICHE = array(
        'SPA' => 'SPA',
        'SRL' => 'SRL',
        'SAS' => 'SAS',
        'SNC' => 'SNC',
        'Libero Professionista' => 'Libero Professionista',
        'Ditta Individuale' => 'Ditta Individuale',
        'SCARL' => 'SCARL',
        'SAPA' => 'SAPA',
        'Società Semplice' => 'Società Semplice',
        'Consorzio' => 'Consorzio',
        'Ente Amministrazione Pubblica' => 'Ente Amministrazione Pubblica',
        'Associazione Cooperativa' => 'Associazione Cooperativa',
        'Studi Professionistici Associati' => 'Studi Professionistici Associati',
    );


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            }
        });


        self::saved(function ($model) {
            self::calcolaProduzione($model);

        });

        self::deleted(function ($model) {
            self::calcolaProduzione($model);
        });

    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente()
    {
        return $this->hasOne(User::class, 'id', 'agente_id');
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function esito()
    {
        return $this->hasOne(EsitoSegnalazione::class, 'id', 'esito_id');
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

    public static function determinaPuoCambiareStato()
    {
        return Auth::user()->hasPermissionTo('admin');

    }


    protected static function calcolaProduzione(Segnalazione $record)
    {

        $mese = $record->created_at->month;
        $anno = $record->created_at->year;
        Log::debug('calcolo segnalazioni in pagamento per $userId:' . $record->agente_id . ' $anno:' . $anno . ' $mese:' . $mese);

        $dataInizio = Carbon::createFromDate($anno, $mese, 1);
        $dataFine = $dataInizio->copy()->endOfMonth();
        $conteggio = self::withoutGlobalScope('filtroOperatore')
            ->whereDate('created_at', '>=', $dataInizio)
            ->whereDate('created_at', '<=', $dataFine)
            ->where('agente_id', $record->agente_id)
            ->where('esito_id', 'gestito')
            ->count();

        $p = ProduzioneOperatore::findOrNewMio($record->agente_id, $anno, $mese);
        $p->user_id = $record->agente_id;
        $p->anno = $anno;
        $p->mese = $mese;
        $p->importo_segnalazioni = self::calcolaImporto($conteggio);
        $p->save();
    }


    protected static function calcolaImporto(int $numeroSegnalazioni): float
    {
        if ($numeroSegnalazioni <= 2) {
            return $numeroSegnalazioni * 20;
        }
        return 40 + ($numeroSegnalazioni - 2) * 45;
    }
}

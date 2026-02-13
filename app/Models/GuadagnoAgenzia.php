<?php

namespace App\Models;

use App\Http\Funzioni\FunzioniCalcoloProvvigioni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function App\meseStrPad;

class GuadagnoAgenzia extends Model
{
    use FunzioniCalcoloProvvigioni;

    protected $table = 'guadagni_agenzia';

    protected $casts = [
        'dettaglio_tipi_contratto' => 'array'
    ];

    protected $fillable = [
        'anno', 'mese'
    ];


    protected static function booted()
    {


        static::saving(function (GuadagnoAgenzia $model) {
            $model->entrate = $model->entrate_contratti + $model->entrate_servizi;
            $model->uscite = $model->uscite_contratti + $model->uscite_servizi;
            $model->utile = $model->entrate - $model->uscite;
        });

    }


    public function calcolaGuadagnoContratti()
    {
        $res = self::calcolaGuadagnoMeseContrattiTelefonia($this->anno, $this->mese);
        $this->entrate_contratti = $res['importo'];
        $this->dettaglio_tipi_contratto = $res['dettaglio'];
        $this->uscite_contratti = self::calcolaUsciteContratti($this->anno, $this->mese);
        $this->save();
    }

    public function calcolaGuadagnoServizi()
    {
        $res = self::calcolaGuadagnoMeseServizi($this->anno, $this->mese);
        $this->entrate_servizi = $res->provvigione_agenzia ?? 0;
        $this->uscite_servizi = $res->provvigione_agente ?? 0;
        $this->save();

    }


    public static function calcolaGuadagnoMeseServizi($anno, $mese)
    {
        $res = ServizioFinanziario::query()
            ->withoutGlobalScope('filtroOperatore')
            ->where('mese_pagamento', $mese . '_' . $anno)
            ->select(DB::raw('sum(provvigione_agente) as provvigione_agente'), DB::raw('sum(provvigione_agenzia) as provvigione_agenzia'))
            ->first();

        return $res;
    }


    /**
     * @param $userId
     * @param $anno
     * @param $mese
     * @return array
     */
    public static function calcolaGuadagnoMeseContrattiTelefonia($anno, $mese): array
    {

        $mese = meseStrPad($mese);
        //Trovo i vari tipi di contratto nel periodo
        $contrattiGroupByTipoContratto = ContrattoTelefonia::query()
            ->withoutGlobalScope('filtroOperatore')
            ->where('mese_pagamento', $mese . '_' . $anno)
            ->groupBy('tipo_contratto_id')
            ->select('tipo_contratto_id', DB::raw('count(*) as conteggio'))
            ->get();


        $importo = 0;
        $dettaglioTipiContratto = [];


        foreach ($contrattiGroupByTipoContratto as $tipoContratto) {
            //Cerco la fascia di retribuzione per quel tipo di contratto
            $soglia = self::determinaSogliaContrattiTelefonia($tipoContratto->conteggio, 4, $tipoContratto->tipo_contratto_id);
            $importoTmp = 0;
            if ($soglia) {
                $importoTmp = $soglia->calcolaGuadagno($tipoContratto->conteggio);
                $importo += $importoTmp;
            }

            $arr = ['numero_contratti' => $tipoContratto->conteggio, 'soglia' => $soglia ? $soglia->descriviFascia() : 'no-soglia', 'importo' => $importoTmp];
            $dettaglioTipiContratto[$tipoContratto->tipo_contratto_id] = $arr;
        }


        Log::debug(__FUNCTION__ . ' importo:' . $importo);

        return ['importo' => $importo, 'dettaglio' => $dettaglioTipiContratto];

    }


    public static function calcolaUsciteContratti($anno, $mese)
    {
        return ProduzioneOperatore::query()
            ->where('anno', $anno)
            ->where('mese', $mese)
            ->where('user_id', '<>', 2)
            ->sum('importo_totale');
    }

}

<?php

namespace App\Models;

use App\Http\Funzioni\FunzioniCalcoloProvvigioni;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function meseStrPad;

class ProduzioneOperatore extends Model
{
    use FunzioniCalcoloProvvigioni;

    protected $table = 'produzioni_operatori';

    public $incrementing = false;

    protected $casts = [
        'dettaglio_tipi_contratto' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'anno',
        'mese',
        'conteggio_ordini_ok',
        'importo',
    ];

    public static function boot()
    {
        parent::boot();

        self::saved(function ($model) {
            Log::debug('saving Produzioneoperatore'.$model->importo_ordini);
            // self::aggiornaGuadagno($model);

            if ($model->wasChanged('importo_ordini')) {
                self::calcolaGuadagnoAgenzia($model->anno, $model->mese);
            }
        });

        self::creating(function ($model) {

            $model->id = self::creaId($model->user_id, $model->anno, $model->mese);
            // self::aggiornaGuadagno($model);
        });

        self::updating(function ($model) {
            // self::aggiornaGuadagno($model);
        });

    }

    /**********************
     * RELAZIONI
     **********************/

    public function agente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fatturaProforma()
    {
        return $this->belongsTo(FatturaProforma::class, 'fattura_proforma_id');
    }

    /**********************
     * ALTRO
     **********************/

    public static function findByIdAnnoMese($id, $anno, $mese)
    {
        $id = self::creaId($id, $anno, $mese);

        return ProduzioneOperatore::find($id);
    }

    public static function creaId($id, $anno, $mese)
    {
        return $id.'_'.$anno.'_'.meseStrPad($mese);
    }

    public function ricalcola()
    {
        $this->calcolaGuadagnoMese();
        $this->save();
        try {
            app(\App\Http\Services\GaraEvalService::class)
                ->ricalcolaMese((int) $this->anno, (int) $this->mese, (int) $this->user_id);
        } catch (\Throwable $e) {
            \Log::error('GaraEvalService in ricalcola: '.$e->getMessage(), [
                'produzione_id' => $this->id,
                'user_id' => $this->user_id,
            ]);
            report($e);
        }
    }

    public static function calcolaTotaliOrdiniMese($userId, $anno, $mese)
    {
        $start = Carbon::createFromDate($anno, $mese, 1);
        $end = $start->copy()->endOfMonth();

        // Telefonia + energia (created_at nel mese): widget "Contratti caricati" include entrambi.
        $byEsito = [];
        foreach ([ContrattoTelefonia::class, ContrattoEnergia::class] as $modelClass) {
            $rows = $modelClass::withoutGlobalScope('filtroOperatore')
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->where('agente_id', $userId)
                ->select('esito_finale', DB::raw('count(*) as conteggio'))
                ->groupBy('esito_finale')
                ->get();
            foreach ($rows as $row) {
                $key = $row->esito_finale ?? '';
                $byEsito[$key] = ($byEsito[$key] ?? 0) + (int) $row->conteggio;
            }
        }

        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;
        $p->conteggio_ordini_ok = $byEsito['ok'] ?? 0;
        $p->conteggio_ordini_ko = $byEsito['ko'] ?? 0;
        $p->conteggio_ordini = array_sum($byEsito);
        $p->save();
    }

    public static function calcolaTotaliOrdiniInPagamento($userId, $anno, $mese)
    {

        Log::debug('calcolo ordini in pagamento per $userId:'.$userId.' $anno:'.$anno.' $mese:'.$mese);
        $startTime = microtime(true);
        $meseAnnoPagamento = meseStrPad($mese).'_'.$anno;
        $inPagamento = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->daLiquidare()
            ->where('mese_pagamento', $meseAnnoPagamento)
            ->where('agente_id', $userId)
            ->count();

        if (false) {
            $rid = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()
                ->where('mese_pagamento', $meseAnnoPagamento)
                ->where('agente_id', $userId)
                ->where('metodo_pagamento', 'rid')
                ->count();

        } else {
            $rid = 0;
        }

        // Log::debug('=>$start:' . $start->format('d/m/Y') . ' $end:' . $end->format('d/m/Y') . ' $ordiniOk:' . $ordiniOk . ' $ordiniKo:' . $ordiniKo . ' $ordini' . $ordini . ' chiamata da ' . debug_backtrace()[1]['function']);

        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;

        $p->conteggio_ordini_in_pagamento = $inPagamento;
        $p->conteggio_rid = $rid;
        $p->calcolaGuadagnoMese();
        $p->save();

    }

    /**
     * @param  $userId
     * @param  $anno
     * @param  $mese
     * @return array
     */
    public function calcolaGuadagnoMese()
    {

        // Trovo i vari tipi di contratto nel periodo (mese_pagamento = mm_YYYY, es. 07_2026)
        // Esclude contratti già pagati all'affiliato.
        $contrattiGroupByTipoContratto = ContrattoTelefonia::query()
            ->daLiquidare()
            ->where('agente_id', $this->user_id)
            ->where('mese_pagamento', meseStrPad($this->mese).'_'.$this->anno)
            ->groupBy('tipo_contratto_id')
            ->select('tipo_contratto_id', DB::raw('count(*) as conteggio'))
            ->get();

        $importo = 0;
        $dettaglioTipiContratto = [];
        foreach ($contrattiGroupByTipoContratto as $tipoContratto) {
            // Cerco la fascia di retribuzione per quel tipo di contratto
            // Recupero listino
            $listinoId = Agente::firstWhere('user_id', $this->user_id)->listino_telefonia_id;

            $soglia = self::determinaSogliaContrattiTelefonia($tipoContratto->conteggio, $listinoId, $tipoContratto->tipo_contratto_id);
            $importoTmp = 0;
            if ($soglia) {
                $importoTmp = $soglia->calcolaGuadagno($tipoContratto->conteggio);
                $importo += $importoTmp;
            }
            $arr = ['numero_contratti' => $tipoContratto->conteggio, 'soglia' => $soglia ? $soglia->descriviFascia() : 'no-soglia', 'importo' => $importoTmp];
            $dettaglioTipiContratto[$tipoContratto->tipo_contratto_id] = $arr;
        }

        Log::debug(__FUNCTION__.' importo:'.$importo);

        $this->dettaglio_tipi_contratto = $dettaglioTipiContratto;
        $this->importo_ordini = $importo;

        return ['importo' => $importo, 'dettaglio' => $dettaglioTipiContratto];

    }

    protected static function calcolaGuadagnoAgenzia($anno, $mese)
    {
        $guadagno = GuadagnoAgenzia::firstOrNew(['anno' => $anno, 'mese' => $mese]);
        $guadagno->calcolaGuadagnoContratti();

    }

    public function ricalcolaProduzione($aggiornaOre = false)
    {
        self::calcolaTotaliOrdiniMese($this->user_id, $this->anno, $this->mese);
        self::calcolaTotaliOrdiniInPagamento($this->user_id, $this->anno, $this->mese);
        ContrattoEnergia::calcolaProduzioneContrattiEnergia($this->user_id, $this->anno, $this->mese);

        // Servizi dismessi: azzera voci legacy così importo_totale (generated) = solo tel+energia.
        $this->refresh();
        $dirty = false;
        foreach (['importo_servizi_finanziari', 'importo_segnalazioni', 'importo_attivazioni_sim'] as $campo) {
            if ((float) ($this->getAttribute($campo) ?? 0) !== 0.0) {
                $this->setAttribute($campo, 0);
                $dirty = true;
            }
        }
        if ($dirty) {
            $this->save();
        }

        try {
            app(\App\Http\Services\GaraEvalService::class)
                ->ricalcolaMese((int) $this->anno, (int) $this->mese, (int) $this->user_id);
        } catch (\Throwable $e) {
            \Log::error('GaraEvalService in ricalcolaProduzione: '.$e->getMessage(), [
                'produzione_id' => $this->id,
                'user_id' => $this->user_id,
            ]);
            report($e);
        }

        $this->refresh();
    }

    /**
     * Totale liquidabile attuale: telefonia + energia (senza gare).
     */
    public function importoTelEnergia(): float
    {
        return round((float) ($this->importo_ordini ?? 0) + (float) ($this->importo_contratti_energia ?? 0), 2);
    }

    /**
     * Totale liquidabile inclusi premi gara del mese.
     */
    public function importoLiquidabile(): float
    {
        $gare = 0.0;
        try {
            $gare = app(\App\Http\Services\GaraEvalService::class)
                ->importoGareUser((int) $this->user_id, (int) $this->anno, (int) $this->mese);
        } catch (\Throwable $e) {
            // tabella assente in ambienti non migrati
        }

        return round($this->importoTelEnergia() + $gare, 2);
    }

    public static function findOrNewMio($agenteId, $anno, $mese)
    {
        return ProduzioneOperatore::findOrNew(self::creaId($agenteId, $anno, $mese));
    }
}

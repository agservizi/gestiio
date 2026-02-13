<?php

namespace App\Models;

use App\Http\Funzioni\FunzioniCalcoloProvvigioni;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function App\decimal_to_time;
use function App\meseStrPad;

class ProduzioneOperatore extends Model
{
    use FunzioniCalcoloProvvigioni;

    protected $table = 'produzioni_operatori';
    public $incrementing = false;

    protected $casts = [
        'dettaglio_tipi_contratto' => 'array'
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
            Log::debug('saving Produzioneoperatore' . $model->importo_ordini);
            //self::aggiornaGuadagno($model);

            if ($model->wasChanged('importo_ordini')) {
                self::calcolaGuadagnoAgenzia($model->anno, $model->mese);
            }
        });


        self::creating(function ($model) {

            $model->id = self::creaId($model->user_id, $model->anno, $model->mese);
            //self::aggiornaGuadagno($model);
        });

        self::updating(function ($model) {
            //self::aggiornaGuadagno($model);
        });

    }


    /**********************
     * RELAZIONI
     **********************/


    public function agente()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function fatturaProforma()
    {
        return $this->hasOne(FatturaProforma::class, 'id', 'fattura_proforma_id');
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
        return $id . '_' . $anno . '_' . meseStrPad($mese);
    }

    public function ricalcola()
    {

        $this->calcolaGuadagnoMese();
        $this->save();

    }


    public static function calcolaTotaliOrdiniMese($userId, $anno, $mese)
    {
        $startTime = microtime(true);

        $start = Carbon::createFromDate($anno, $mese, 1);

        $end = $start->copy()->endOfMonth();


        $ordini = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->where('agente_id', $userId)
            ->select('esito_finale', DB::raw('count(esito_finale) as conteggio'))
            ->groupBy('esito_finale')
            ->get()->keyBy('esito_finale');

        $p = ProduzioneOperatore::findOrNewMio($userId, $anno, $mese);
        $p->user_id = $userId;
        $p->anno = $anno;
        $p->mese = $mese;
        $p->conteggio_ordini_ok = $ordini['ok']->conteggio ?? 0;
        $p->conteggio_ordini_ko = $ordini['ko']->conteggio ?? 0;
        $p->conteggio_ordini = $ordini->sum('conteggio');
        $p->save();


    }

    public static function calcolaTotaliOrdiniInPagamento($userId, $anno, $mese)
    {

        Log::debug('calcolo ordini in pagamento per $userId:' . $userId . ' $anno:' . $anno . ' $mese:' . $mese);
        $startTime = microtime(true);
        $meseAnnoPagamento = $mese . '_' . $anno;
        $inPagamento = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->where('mese_pagamento', $meseAnnoPagamento)
            ->where('agente_id', $userId)
            ->count();

        if (false) {
            $rid = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->where('mese_pagamento', $meseAnnoPagamento)
                ->where('agente_id', $userId)
                ->where('metodo_pagamento', 'rid')
                ->count();

        } else {
            $rid = 0;
        }


        //Log::debug('=>$start:' . $start->format('d/m/Y') . ' $end:' . $end->format('d/m/Y') . ' $ordiniOk:' . $ordiniOk . ' $ordiniKo:' . $ordiniKo . ' $ordini' . $ordini . ' chiamata da ' . debug_backtrace()[1]['function']);


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
     * @param $userId
     * @param $anno
     * @param $mese
     * @return array
     */
    public function calcolaGuadagnoMese()
    {

        //Trovo i vari tipi di contratto nel periodo
        $contrattiGroupByTipoContratto = ContrattoTelefonia::query()
            ->where('agente_id', $this->user_id)
            ->where('mese_pagamento', $this->mese . '_' . $this->anno)
            ->groupBy('tipo_contratto_id')
            ->select('tipo_contratto_id', DB::raw('count(*) as conteggio'))
            ->get();


        $importo = 0;
        $dettaglioTipiContratto = [];
        foreach ($contrattiGroupByTipoContratto as $tipoContratto) {
            //Cerco la fascia di retribuzione per quel tipo di contratto
            //Recupero listino
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

        Log::debug(__FUNCTION__ . ' importo:' . $importo);

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

    }

    public static function findOrNewMio($agenteId, $anno, $mese)
    {
        return ProduzioneOperatore::findOrNew(self::creaId($agenteId, $anno, $mese));
    }

}

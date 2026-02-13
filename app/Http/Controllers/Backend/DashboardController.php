<?php

namespace App\Http\Controllers\Backend;

use App\Http\MieClassiCache\CacheUnaVoltaAlGiorno;
use App\Models\ContrattoTelefonia;
use App\Models\ProduzioneOperatore;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use function App\mese;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if(!$user, 403);

        dispatch(function () {
            CacheUnaVoltaAlGiorno::get();
        })->afterResponse();

        if ($user->hasPermissionTo('admin')) {
            return $this->showAdmin($request);
        } else if ($user->hasPermissionTo('supervisore')) {
            if ($user->hasPermissionTo('servizio_contratti_telefonia')) {
                return response()->redirectTo(action([ContrattoTelefoniaController::class, 'index']));
            }
            if ($user->hasPermissionTo('servizio_caf_patronato')) {
                return response()->redirectTo(action([CafPatronatoController::class, 'index']));
            }

        } else {
            return $this->showAgente();
        }

    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    protected function showAdmin($request)
    {
        $id = Auth::user()->id;
        $this->elencoMesi();


        $mese = $request->input('mese', now()->format('Y_m'));

        list($filtroAnno, $filtroMese) = explode('_', $mese);


        $contratti = ContrattoTelefonia::query()
            ->with('agente')
            ->with('tipoContratto.gestore')
            ->with('esito')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $servizi = \App\Models\CafPatronato::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $tikets = Ticket::query()
            ->with('utente')
            ->orderByDesc('id')
            ->limit(1)
            ->where('stato', '<>', 'chiuso')
            ->get();

        $conteggioTikets = Ticket::groupBy('stato')
            ->select('stato', DB::raw('count(*) as conteggio'))
            ->get()->keyBy('stato');


        return view('Backend.Dashboard.showAdmin', [
            'titoloPagina' => 'Ciao '.Auth::user()->nome,
            'mainMenu' => 'dashboard',
            'contratti' => $contratti,
            'servizi' => $servizi,
            'tikets' => $tikets,
            'conteggioTikets' => $conteggioTikets,
            'datiTortaEsiti' => $this->datiTortaEsiti(),
            'produzioneMese' => ProduzioneOperatore::find($id . '_' . $mese),
            'elencoMesi' => $this->elencoMesi(),
            'mese' => $mese,
            'filtroAnno' => $filtroAnno,
            'filtroMese' => $filtroMese
        ]);

    }


    /**
     * @return array
     */
    protected function elencoMesi()
    {
        $arr = [];
        $dataInizio = now()->startOfMonth();
        $dataFine = Carbon::createFromDate(config('configurazione.primoAnno'), config('configurazione.primoMese'));
        $arr[$dataInizio->format('Y_m')] = 'Questo mese';
        while ($dataInizio->greaterThanOrEqualTo($dataFine)) {
            $dataInizio->subMonthNoOverflow();
            $arr[$dataInizio->format('Y_m')] = ucfirst($dataInizio->translatedFormat('M Y'));
        }
        return $arr;
    }

    protected function showAgente()
    {
        $id = Auth::user()->id;

        $questoMese = now();
        $mesePrecedente = $questoMese->copy()->subMonths(1);

        return view('Backend.Dashboard.showAgente', [
            'titoloPagina' => 'Ciao '.Auth::user()->nome,
            'mainMenu' => 'dashboard',
            'record' => Auth::user(),
            'produzioneMese' => ProduzioneOperatore::findByIdAnnoMese($id, $questoMese->year, $questoMese->month),
            'produzioneMesePrecedente' => ProduzioneOperatore::findByIdAnnoMese($id, $mesePrecedente->year, $mesePrecedente->month),
            'datiBarreOrdini' => $this->datiBarreOrdini(now()->year),

        ]);

    }


    protected function datiTortaEsiti()
    {

        $esitiFinali = ContrattoTelefonia::query()
            ->groupBy('esito_finale')
            ->select('esito_finale', DB::raw('count(*) as conteggio'))
            ->get();

        $arrValori = [];
        $arrTesti = [];
        $arrColori = [];
        $totale = 0;
        foreach ($esitiFinali as $o) {
            $arrValori[] = $o->conteggio;
            $totale += $o->conteggio;
            $arrTesti[] = ucfirst(str_replace('-', ' ', $o->esito_finale));
            $arrColori[] = ContrattoTelefonia::ESITI[$o->esito_finale];
        }

        return [
            'data' => $arrValori,
            'backgroundColor' => $arrColori,
            'labels' => $arrTesti,
            'totale' => $totale
        ];
    }

    protected function datiBarreOrdini($anno)
    {

        $arrOk = [];
        $arrMese = [];

        $produzioneAnno = ProduzioneOperatore::query()
            ->where('user_id', Auth::id())
            ->where('anno', $anno)
            ->get()->keyBy('mese');

        for ($mese = 1; $mese <= 12; $mese++) {
            if (isset($produzioneAnno[$mese])) {
                $arrOk[] = $produzioneAnno[$mese]->importo_totale;
            } else {
                $arrOk[] = 0;
            }
            $arrMese[] = mese($mese);
        }


        return [
            'arrOk' => $arrOk,
            'arrMese' => $arrMese
        ];
    }


}

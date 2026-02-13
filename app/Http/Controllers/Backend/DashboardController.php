<?php

namespace App\Http\Controllers\Backend;

use App\Http\MieClassiCache\CacheUnaVoltaAlGiorno;
use App\Models\CafPatronato;
use App\Models\ContrattoEnergia;
use App\Models\ClienteAssistenza;
use App\Models\ContrattoTelefonia;
use App\Models\ProduzioneOperatore;
use App\Models\RichiestaAssistenza;
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

        CacheUnaVoltaAlGiorno::get();

        if ($user->hasPermissionTo('admin')) {
            return $this->showAdmin($request);
        } else if ($user->hasPermissionTo('supervisore')) {
            return $this->showSupervisore($request);
        } else {
            return $this->showAgente();
        }

    }

    protected function showSupervisore(Request $request)
    {
        $this->elencoMesi();
        $mese = $request->input('mese', now()->format('Y_m'));
        [$filtroAnno, $filtroMese] = explode('_', $mese);

        $contratti = ContrattoTelefonia::query()
            ->with('agente')
            ->with('tipoContratto.gestore')
            ->with('esito')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $servizi = CafPatronato::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $ticketRecenti = Ticket::query()
            ->with('utente')
            ->with('causaleTicket')
            ->orderByDesc('id')
            ->limit(5)
            ->where('stato', '<>', 'chiuso')
            ->get();

        $conteggioTikets = Ticket::groupBy('stato')
            ->select('stato', DB::raw('count(*) as conteggio'))
            ->get()
            ->keyBy('stato');

        $kpiSupervisore = [
            'contratti_telefonia_mese' => ContrattoTelefonia::query()
                ->whereYear('data', $filtroAnno)
                ->whereMonth('data', $filtroMese)
                ->count(),
            'contratti_energia_mese' => ContrattoEnergia::query()
                ->whereYear('data', $filtroAnno)
                ->whereMonth('data', $filtroMese)
                ->count(),
            'pratiche_caf_mese' => CafPatronato::query()
                ->whereYear('data', $filtroAnno)
                ->whereMonth('data', $filtroMese)
                ->count(),
            'ticket_aperti' => Ticket::query()->where('stato', '<>', 'chiuso')->count(),
            'pratiche_ferme' => CafPatronato::query()
                ->whereIn('esito_id', ['bozza', 'da-gestire'])
                ->whereDate('created_at', '<=', now()->subDays(7))
                ->count(),
        ];

        $alertSupervisore = [
            'caf_bloccate' => CafPatronato::query()
                ->whereNotNull('motivo_ko')
                ->where('motivo_ko', '!=', '')
                ->count(),
            'ticket_aperti_oltre_48h' => Ticket::query()
                ->where('stato', '<>', 'chiuso')
                ->whereDate('created_at', '<=', now()->subDays(2))
                ->count(),
        ];

        return view('Backend.Dashboard.showSupervisore', [
            'titoloPagina' => 'Ciao ' . Auth::user()->nome,
            'mainMenu' => 'dashboard',
            'contratti' => $contratti,
            'servizi' => $servizi,
            'ticketRecenti' => $ticketRecenti,
            'conteggioTikets' => $conteggioTikets,
            'kpiSupervisore' => $kpiSupervisore,
            'alertSupervisore' => $alertSupervisore,
            'datiTortaEsiti' => $this->datiTortaEsiti(),
            'elencoMesi' => $this->elencoMesi(),
            'mese' => $mese,
            'filtroAnno' => $filtroAnno,
            'filtroMese' => $filtroMese,
        ]);
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

        $kpiDashboard = [
            'richieste_assistenza_totali' => RichiestaAssistenza::count(),
            'richieste_assistenza_oggi' => RichiestaAssistenza::whereDate('created_at', now())->count(),
            'clienti_assistenza_totali' => ClienteAssistenza::count(),
            'ticket_aperti' => Ticket::where('stato', '<>', 'chiuso')->count(),
        ];

        $alertDashboard = [
            'richieste_senza_credenziali' => RichiestaAssistenza::query()
                ->where(function ($q) {
                    $q->whereNull('nome_utente')->orWhere('nome_utente', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('password')->orWhere('password', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('pin')->orWhere('pin', '');
                })
                ->count(),
            'clienti_senza_contatti' => ClienteAssistenza::query()
                ->where(function ($q) {
                    $q->whereNull('email')->orWhere('email', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('telefono')->orWhere('telefono', '');
                })
                ->count(),
        ];

        $azioniRapide = RichiestaAssistenza::query()
            ->with(['cliente:id,nome,cognome,codice_fiscale,email,telefono', 'prodotto:id,nome'])
            ->where(function ($q) {
                $q->whereNull('nome_utente')->orWhere('nome_utente', '');
            })
            ->orWhere(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->orWhere(function ($q) {
                $q->whereNull('pin')->orWhere('pin', '');
            })
            ->latest('id')
            ->limit(8)
            ->get();


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
            'filtroMese' => $filtroMese,
            'kpiDashboard' => $kpiDashboard,
            'alertDashboard' => $alertDashboard,
            'azioniRapide' => $azioniRapide,
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

        $kpiAgente = [
            'miei_ticket_aperti' => Ticket::where('user_id', $id)->where('stato', '<>', 'chiuso')->count(),
            'miei_ticket_oggi' => Ticket::where('user_id', $id)->whereDate('created_at', now())->count(),
            'ticket_aperti_totali' => Ticket::where('stato', '<>', 'chiuso')->count(),
        ];

        $ticketDaGestire = Ticket::query()
            ->with(['utente:id,nome,cognome', 'causaleTicket:id,nome'])
            ->where('user_id', $id)
            ->where('stato', '<>', 'chiuso')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('Backend.Dashboard.showAgente', [
            'titoloPagina' => 'Ciao '.Auth::user()->nome,
            'mainMenu' => 'dashboard',
            'record' => Auth::user(),
            'produzioneMese' => ProduzioneOperatore::findByIdAnnoMese($id, $questoMese->year, $questoMese->month),
            'produzioneMesePrecedente' => ProduzioneOperatore::findByIdAnnoMese($id, $mesePrecedente->year, $mesePrecedente->month),
            'datiBarreOrdini' => $this->datiBarreOrdini(now()->year),
            'kpiAgente' => $kpiAgente,
            'ticketDaGestire' => $ticketDaGestire,

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

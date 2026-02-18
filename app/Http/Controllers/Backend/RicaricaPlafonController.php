<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\MovimentoPortafoglio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use function App\getInputNumero;

class RicaricaPlafonController extends Controller
{
    public function show()
    {
        $filtroAgenteId = (int) request()->input('filtro_agente_id');
        $walletTabs = array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases());
        $tabPortafoglio = (string) request()->input('tab_portafoglio', TipiPortafoglioEnum::SERVIZI->value);
        if (!in_array($tabPortafoglio, $walletTabs, true)) {
            $tabPortafoglio = TipiPortafoglioEnum::SERVIZI->value;
        }

        $record = new MovimentoPortafoglio();
        $record->agente_id = null;
        $record->portafoglio = $tabPortafoglio;

        $ultimeRicariche = MovimentoPortafoglio::query()
            ->withoutGlobalScopes()
            ->when($filtroAgenteId > 0, function ($query) use ($filtroAgenteId) {
                $query->where('agente_id', $filtroAgenteId);
            })
            ->where('portafoglio', $tabPortafoglio)
            ->where(function ($query) {
                $query->where('descrizione', 'like', 'Ricarica%')
                    ->orWhere('descrizione', 'like', '%Stripe%');
            })
            ->latest('id')
            ->paginate(10);

        $agenteIds = [];
        foreach ($ultimeRicariche as $movimento) {
            if (!empty($movimento->agente_id)) {
                $agenteIds[] = (int) $movimento->agente_id;
            }
        }

        $agenti = User::query()
            ->whereIn('id', array_values(array_unique($agenteIds)))
            ->get(['id', 'nome', 'cognome'])
            ->keyBy('id');

        foreach ($ultimeRicariche as $movimento) {
            $descrizione = (string) $movimento->descrizione;
            $tipo = 'Altro';

            if (stripos($descrizione, 'manuale') !== false) {
                $tipo = 'Manuale admin → agente';
            } elseif (stripos($descrizione, 'stripe') !== false) {
                $tipo = 'Autonomia agente (carta)';
            }

            $agente = $agenti->get((int) $movimento->agente_id);
            $movimento->agente_nominativo = $agente ? $agente->nominativo() : ('Utente #' . $movimento->agente_id);
            $movimento->tipo_ricarica = $tipo;
        }

        $agenteSelezionato = null;
        if ($filtroAgenteId > 0) {
            $agenteSelezionato = User::query()->with('agente')->find($filtroAgenteId);
        }

        return view('Backend.RicaricaPlafond.show', [
            'titoloPagina' => 'Ricarica plafond',
            'record' => $record,
            'ultimeRicariche' => $ultimeRicariche,
            'filtroAgenteId' => $filtroAgenteId,
            'tabPortafoglio' => $tabPortafoglio,
            'walletTabs' => TipiPortafoglioEnum::cases(),
            'agenteSelezionato' => $agenteSelezionato,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'agente_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'portafoglio' => ['required', Rule::in(array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases()))],
            'importo' => ['required'],
        ]);

        $importo = getInputNumero($request->input('importo'));
        if ($importo <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'importo' => 'Inserisci un importo maggiore di zero.',
            ]);
        }


        $movimento = new MovimentoPortafoglio();
        $movimento->agente_id = $request->input('agente_id');
        $movimento->importo = $importo;
        $movimento->descrizione = 'Ricarica manuale portafoglio';
        $movimento->portafoglio = $request->input('portafoglio');
        $movimento->save();

        $alertMessage = new AlertMessage();
        $alertMessage->messaggio('Portafoglio ricaricato')->flash();
        return redirect()->back();

    }
}

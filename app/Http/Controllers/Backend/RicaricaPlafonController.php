<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\MovimentoPortafoglio;
use App\Models\User;
use Illuminate\Http\Request;
use function App\getInputNumero;

class RicaricaPlafonController extends Controller
{
    public function show()
    {
        $agenteId = (int) request()->input('agente_id');
        $record = new MovimentoPortafoglio();
        $record->agente_id = $agenteId ?: null;

        $ultimeRicariche = MovimentoPortafoglio::query()
            ->withoutGlobalScopes()
            ->when($agenteId > 0, function ($query) use ($agenteId) {
                $query->where('agente_id', $agenteId);
            })
            ->where(function ($query) {
                $query->where('descrizione', 'like', 'Ricarica%')
                    ->orWhere('descrizione', 'like', '%Stripe%');
            })
            ->latest('id')
            ->limit(20)
            ->get();

        $agenti = User::query()
            ->whereIn('id', $ultimeRicariche->pluck('agente_id')->filter()->unique()->values())
            ->get(['id', 'nome', 'cognome'])
            ->keyBy('id');

        $ultimeRicariche = $ultimeRicariche->map(function (MovimentoPortafoglio $movimento) use ($agenti) {
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

            return $movimento;
        });

        return view('Backend.RicaricaPlafond.show', [
            'titoloPagina' => 'Ricarica plafond',
            'record' => $record,
            'ultimeRicariche' => $ultimeRicariche,
        ]);
    }

    public function store(Request $request)
    {


        $movimento = new MovimentoPortafoglio();
        $movimento->agente_id = $request->input('agente_id');
        $movimento->importo = getInputNumero($request->input('importo'));
        $movimento->descrizione = 'Ricarica manuale portafoglio';
        $movimento->portafoglio = $request->input('portafoglio');
        $movimento->save();

        $alertMessage = new AlertMessage();
        $alertMessage->messaggio('Portafoglio ricaricato')->flash();
        return redirect()->back();

    }
}

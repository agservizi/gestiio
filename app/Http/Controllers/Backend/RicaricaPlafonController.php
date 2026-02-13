<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\MovimentoPortafoglio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function App\getInputNumero;

class RicaricaPlafonController extends Controller
{
    public function show()
    {
        $record = new MovimentoPortafoglio();
        $record->agente_id = \request()->input('agente_id');
        return view('Backend.RicaricaPlafond.show', [
            'titoloPagina' => 'Ricarica plafond',
            'record' => $record,
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

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agente;
use App\Models\FasciaListinoTipoContratto;
use App\Models\ProduzioneOperatore;
use App\Models\TipoContratto;
use App\Models\User;
use Illuminate\Http\Request;

class ProfiloController extends Controller
{
    public function show()
    {
        $record = \Auth::user();
        abort_if(!$record, 404, 'Questo operatore non esiste');
        $controller = get_class($this);

        $questoMese = now();

        $mandati = \App\Models\Mandato::with('gestore')->where('agente_id', \Auth::id())->get()->pluck('gestore_id')->toArray();

        $tipiContratto = TipoContratto::with('gestore')->whereIn('gestore_id', $mandati)->orderBy('nome')->get();
        return view('Backend.Profilo.show', [
            'record' => $record,
            'tipiContratto' => $tipiContratto,
            'titoloPagina' => 'Profilo',
            'controller' => $controller,
        ]);
    }

    public function showListino($mandatoId)
    {


        $agente = Agente::firstWhere('user_id', \Auth::id());
        $tipoContratto = TipoContratto::find($mandatoId);
        abort_if(!$tipoContratto,404);
        return view('Backend.Profilo.show.showListino', [
            'records' => FasciaListinoTipoContratto::where('listino_id', $agente->listino_telefonia_id)->where('tipo_contratto_id', $mandatoId)->get(),
            'titoloPagina' => 'Listino '.$tipoContratto->nome,
        ]);
    }
}

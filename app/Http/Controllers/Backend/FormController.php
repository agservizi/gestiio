<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MessaggioFormAssistenza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    public function index()
    {
        $records = MessaggioFormAssistenza::with('userRisposto')->paginate();

        return view('Backend.DatiForm.index', [
            'records' => $records->withQuerystring(),
            'titoloPagina' => 'Elenco ' . MessaggioFormAssistenza::NOME_PLURALE,
            'controller' => FormController::class,

        ]);
    }


    public function show($id)
    {
        $record = MessaggioFormAssistenza::find($id);
        abort_if(!$record, 404);


        return view('Backend.DatiForm.modalShow', [
            'record' => $record,
            'titoloPagina' => 'Dati richiesta',
            'controller' => FormController::class
        ]);

    }


    public function risposto($id)
    {
        $record = MessaggioFormAssistenza::find($id);
        abort_if(!$record, 404);
        $record->risposta_user_id = Auth::id();
        $record->save();

        return ['success' => true,'id'=>$id, 'html' => '<span class="badge badge-light-success">Risposto ' . $record->userRisposto->nominativo() . ' ' . $record->updated_at->format('d/m/Y H:i:s') . '</span>'];
    }
}

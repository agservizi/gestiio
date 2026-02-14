<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\AllegatoMessaggioTicket;
use App\Models\ContrattoTelefonia;
use App\Models\MessaggioTicket;
use App\Models\Notifica;
use App\Models\User;
use App\Notifications\NotificaNuovoTicketAdAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Ticket;
use DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }


    /**
     * Show the form for creating a new resource.
     *
        * @return mixed
     */
    public function create()
    {
        $record = new Ticket();
        $contratti = ContrattoTelefonia::delCliente()->get();
        $arr = [];
        foreach ($contratti as $c) {
            $arr[$c->id] = $c->tipoContratto->nome;
        }

        return view('Frontend.Ticket.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Ticket::NOME_SINGOLARE,
            'controller' => get_class($this),
            'contratti' => $arr,
            'breadcrumbs' => [action([TicketController::class, 'index']) => 'Torna a elenco ' . Ticket::NOME_PLURALE]

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|bool[]
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new Ticket();
        $this->salvaDati($record, $request);

        dispatch(function () use ($record) {

            Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">' . $record->oggetto . '</span> da cliente <span class="fw-bold">' . $this->currentUser()->nominativo() . '</span>');

            User::find(2)->notify(new NotificaNuovoTicketAdAdmin($record));
        })->afterResponse();


        $datiRitorno = new DatiRitorno();
        $datiRitorno->success(true)->chiudiDialog(true)->oggettoReload('kt_help', view('Frontend.AreaUtente.elencoTickets'));
        return $datiRitorno->getArray();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function show($id)
    {
        $record = Ticket::with('messaggi.utente')
            ->with('messaggi.allegati')
            ->find($id);
        abort_if(!$record, 404, 'Questo ticket non esiste');

        dispatch(function () use ($record) {
            MessaggioTicket::where('ticket_id', $record->id)->where('user_id', '<>', Auth::id())->whereNull('letto')->update(['letto' => now()]);
        })->afterResponse();


        return view('Frontend.Ticket.show', [
            'record' => $record,
            'controller' => TicketController::class,
            'titoloPagina' => 'Visualizzazione ' . ucfirst(Ticket::NOME_SINGOLARE),
            'breadcrumbs' => [action([TicketController::class, 'index']) => 'Torna a elenco ' . Ticket::NOME_PLURALE]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function edit($id)
    {
        $record = Ticket::find($id);
        abort_if(!$record, 404, 'Questo ticket non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Frontend.Ticket.edit', [
            'record' => $record,
            'controller' => TicketController::class,
            'titoloPagina' => 'Modifica ' . Ticket::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([TicketController::class, 'index']) => 'Torna a elenco ' . Ticket::NOME_PLURALE]

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return array|bool[]
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'messaggio' => ['required']
        ]);


        $record = Ticket::find($id);
        abort_if(!$record, 404, 'Questo ' . Ticket::NOME_SINGOLARE . ' non esiste');

        $messaggio = new MessaggioTicket();
        $messaggio->ticket_id = $record->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();
        $record->touch();
        AllegatoMessaggioTicket::where('uid', $messaggio->uid)->whereNull('messaggio_id')->update(['messaggio_id' => $messaggio->id, 'uid' => null]);


        $datiRitorno = new DatiRitorno();
        $datiRitorno->success(true)->chiudiDialog(true)->oggettoReload('kt_help', view('Frontend.AreaUtente.elencoTickets'));
        return $datiRitorno->getArray();


    }


    /**
     * @param Ticket $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->stato = 'aperto';
            $model->user_id = Auth::id();
        }

        //Ciclo su campi
        $campi = [
            'oggetto' => 'app\getInputUcfirst',
            'contratto_id' => '',
            'tipo' => '',
            'uid' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if ($request->input('contratto_id')) {
            $contratto = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')->find($request->input('contratto_id'));
            if ($contratto && $contratto->agente_id) {
                $model->agente_id = $contratto->agente_id;
            }
        }


        $model->save();

        $messaggio = new MessaggioTicket();
        $messaggio->ticket_id = $model->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();

        AllegatoMessaggioTicket::where('uid', $model->uid)->whereNull('messaggio_id')->update(['messaggio_id' => $messaggio->id, 'uid' => null]);

        return $model;
    }


    protected function rules($id = null)
    {


        $rules = [
            'oggetto' => ['required', 'max:255'],
            'tipo' => ['required', 'max:255'],
            'messaggio' => ['required'],
            'contratto_id' => ['required'],
        ];

        return $rules;
    }


    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoMessaggioTicket();

        if ($request->file('file')) {
            $filePath = $request->file('file');
            $estensione = $filePath->extension();
            $fileName = Str::ulid() . '.' . $estensione;
            $cartella = config('configurazione.allegati_ticket.cartella');
            $request->file('file')->storeAs($cartella, $fileName);
            $file->path_filename = $cartella . '/' . $fileName;
            $file->filename_originale = $filePath->getClientOriginalName();
            $file->mime_type = $filePath->getMimeType();
            $contenuto = file_get_contents($filePath->getRealPath());
            $file->file_contenuto_base64 = $contenuto !== false ? base64_encode($contenuto) : null;
            $file->uid = $request->input('uid');
            $file->dimensione_file = $filePath->getSize();
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName]);

        }
        abort(404, 'File non presente');

    }

    public function downloadAllegato($messaggioId, $allegatoId)
    {

        $record = AllegatoMessaggioTicket::find($allegatoId);
        abort_if(!$record, 404, 'Questo allegato non esiste');
        abort_if($record->messaggio_id != $messaggioId, 404, 'Questo allegato non esiste');

        if ($record->file_contenuto_base64) {
            $contenuto = base64_decode($record->file_contenuto_base64, true);
            if ($contenuto !== false) {
                return response($contenuto, 200, [
                    'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . addslashes($record->filename_originale) . '"',
                ]);
            }
        }

        return response()->download(Storage::path($record->path_filename), $record->filename_originale);

    }


    public function deleteAllegato(Request $request)
    {
        $record = AllegatoMessaggioTicket::find($request->input('id'));
        abort_if(!$record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente' . $record->path_filename);
        $record->delete();
        return $record->path_filename;
    }


}

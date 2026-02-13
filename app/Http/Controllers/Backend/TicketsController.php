<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassiCache\CacheConteggioTicketsDaLeggere;
use App\Http\MieClassiCache\CacheUnaVoltaAlGiorno;
use App\Models\AllegatoMessaggioTicket;
use App\Models\LetturaTicket;
use App\Models\Notifica;
use App\Models\SpedizioneBrt;
use App\Models\Ticket;
use App\Models\ContrattoTelefonia;
use App\Models\MessaggioTicket;
use App\Models\User;
use App\Notifications\NotificaAggiornamentoTicketAUtente;
use App\Notifications\NotificaLetturaTicket;
use App\Notifications\NotificaNuovoTicketAdAdmin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketsController extends Controller
{

    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $records = $this->applicaFiltri($request);

        return view('Backend.Tickets.index')->with([
            'records' => $records->paginate()->withQueryString(),
            'filtro' => false,
            'controller' => get_class($this),
            'titoloPagina' => ucfirst(Ticket::NOME_PLURALE),
            'admin' => Auth::user()->hasPermissionTo('admin'),
            'conFiltro' => $this->conFiltro

        ]);

    }


    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {
        $queryBuilder = Ticket::query()
            ->with('utente')
            ->with('causaleTicket:id,descrizione_causale')
            ->with('lettura')
            ->orderByDesc('id');

        if (Auth::user()->hasPermissionTo('agente')) {
            $queryBuilder->where('agente_id', Auth::id());
        }

        if ($request->input('stato')) {
            $queryBuilder->where('stato', $request->input('stato'));
            $this->conFiltro = true;
        }


        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {


        $record = new Ticket();

        if ($request->input('servizio_type')) {
            switch ($request->input('servizio_type')) {
                case 'spedizione-brt':
                    $servizio = SpedizioneBrt::find($request->input('servizio_id'));
                    $record->servizio_type = SpedizioneBrt::class;
                    break;

                case 'contratto-telefonia':
                    $servizio = ContrattoTelefonia::find($request->input('servizio_id'));
                    $record->servizio_type = ContrattoTelefonia::class;
                    break;

            }

            if ($servizio) {
                $record->servizio()->associate($servizio);
            }
        }


        return view('Backend.Tickets.create', [
            'controller' => get_class($this),
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Ticket::NOME_SINGOLARE,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'oggetto' => ['required'],
            'messaggio' => ['required'],
            'servizio_type' => ['required'],
        ]);


        $ticket = new Ticket();
        $ticket->servizio_id = $request->input('servizio_id');
        $ticket->servizio_type = $request->input('servizio_type');

        $ticket->user_id = Auth::id();

        if (Auth::user()->hasPermissionTo('agente')) {
            $ticket->agente_id = Auth::id();
        } else {
            $ticket->agente_id = $ticket->servizio->agente_id;
        }

        $ticket->oggetto = $request->input('oggetto');
        $ticket->stato = 'aperto';
        $ticket->causale_ticket_id = $request->input('causale_ticket_id');
        $ticket->uid = $request->input('uid');
        $ticket->da_tipo_utente = $this->determinaDaTipoUtente();
        $ticket->a_tipo_utente = $this->determinaATipoUtente($ticket->da_tipo_utente);
        $ticket->save();

        $messaggio = new MessaggioTicket();
        $messaggio->ticket_id = $ticket->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();

        $lettura = new LetturaTicket();
        $lettura->ticket_id = $ticket->id;
        $lettura->user_id = Auth::id();
        $lettura->messaggio_letto = 1;
        $lettura->save();

        $lettura = new LetturaTicket();
        $lettura->ticket_id = $ticket->id;
        $lettura->user_id = 2;
        $lettura->messaggio_letto = 0;
        $lettura->save();


        AllegatoMessaggioTicket::where('uid', $messaggio->uid)->whereNull('messaggio_id')->update(['messaggio_id' => $messaggio->id, 'uid' => null]);

        dispatch(function () use ($ticket) {

            if ($ticket->da_tipo_utente == 'admin') {
                Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">' . $ticket->oggetto . '</span> da agente <span class="fw-bold">' . Auth::user()->nominativo() . '</span>');
                $utente = User::find($ticket->user_id);
                $utente->notify(new NotificaNuovoTicketAdAdmin($ticket));
            } else {
                $utente = User::find($ticket->agente_id);
                $utente->notify(new NotificaNuovoTicketAdAdmin($ticket));
            }


        })->afterResponse();

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $record = Ticket::with('messaggi.utente')
            ->with('messaggi.allegati')
            ->find($id);

        abort_if(!$record, 404, 'Questo ticket non esiste');
        //abort_if(!$record->contratto, 404, 'Questo ticket non esiste');

        dispatch(function () use ($record) {

            $lettura = LetturaTicket::where('ticket_id', $record->id)->where('user_id', Auth::id())->first();
            if ($lettura && !$lettura->messaggio_letto) {
                LetturaTicket::where('ticket_id', $record->id)->where('user_id', '<>', Auth::id())->get()->each(function ($da) use ($record) {
                    User::find($da->user_id)->notify(new NotificaLetturaTicket($record));
                });
            }

            LetturaTicket::where('ticket_id', $record->id)->where('user_id', Auth::id())->get()->each(function ($record) {
                $record->update(['messaggio_letto' => 1, 'data_lettura' => now()]);
            });

            MessaggioTicket::where('ticket_id', $record->id)->where('user_id', '<>', Auth::id())->whereNull('letto')->update(['letto' => now()]);
        })->afterResponse();


        return view('Backend.Tickets.show', [
            'controller' => get_class($this),
            'record' => $record,
            'titoloPagina' => $record->oggetto,
            'admin' => Auth::user()->hasPermissionTo('admin'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->hasPermissionTo('admin')) {

        } else {
            $request->validate([
                'messaggio' => ['required']
            ]);

        }

        $ticket = Ticket::find($id);
        abort_if(!$ticket, 404, 'Questo ' . Ticket::NOME_SINGOLARE . ' non esiste');
        if ($request->input('stato')) {
            $ticket->stato = $request->input('stato');
            $ticket->save();
        }

        if ($request->input('messaggio')) {
            $messaggio = new MessaggioTicket();
            $messaggio->ticket_id = $ticket->id;
            $messaggio->user_id = Auth::id();
            $messaggio->messaggio = $request->input('messaggio');
            $messaggio->save();
            $ticket->touch();

            $ticket = Ticket::find($messaggio->ticket_id);

            LetturaTicket::where('ticket_id', $messaggio->ticket_id)->where('user_id', '<>', Auth::id())->get()->each(function ($record) {
                $record->update(['messaggio_letto' => 0]);
            });

            dispatch(function () use ($ticket) {
                $utente = User::find($ticket->user_id);
                $utente->notify(new NotificaAggiornamentoTicketAUtente($ticket));
            })->afterResponse();


        }


        return $this->backToIndex();


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    protected function determinaDaTipoUtente()
    {
        if (Auth::user()->hasPermissionTo('admin')) {
            return 'admin';
        } elseif (Auth::user()->hasAnyPermission(['agente', 'supervisore'])) {
            return 'agente';
        }
    }


    protected function determinaATipoUtente($daTipoUtente)
    {
        return $daTipoUtente == 'admin' ? 'agente' : 'admin';
    }

}

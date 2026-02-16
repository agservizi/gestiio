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
use Illuminate\Validation\Rule;

class TicketsController extends Controller
{

    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
        * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $records = $this->applicaFiltri($request)->paginate();
        $records->appends($request->query());

        return view('Backend.Tickets.index')->with([
            'records' => $records,
            'filtro' => false,
            'controller' => get_class($this),
            'titoloPagina' => ucfirst(Ticket::NOME_PLURALE),
            'admin' => $authUser->hasPermissionTo('admin'),
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

        /** @var User $authUser */
        $authUser = Auth::user();
        if ($authUser->hasPermissionTo('agente')) {
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
        * @return \Illuminate\Contracts\View\View
     */
    public function create(Request $request)
    {

        /** @var User $authUser */
        $authUser = Auth::user();

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

        $agentiDestinatari = collect();
        $supervisoriDestinatari = collect();
        if ($authUser->hasPermissionTo('admin')) {
            $agentiDestinatari = User::query()
                ->whereHas('permissions', function ($query) {
                    $query->where('name', 'agente');
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);

            $supervisoriDestinatari = User::query()
                ->whereHas('permissions', function ($query) {
                    $query->where('name', 'supervisore');
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);
        }


        return view('Backend.Tickets.create', [
            'controller' => get_class($this),
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Ticket::NOME_SINGOLARE,
            'admin' => $authUser->hasPermissionTo('admin'),
            'agentiDestinatari' => $agentiDestinatari,
            'supervisoriDestinatari' => $supervisoriDestinatari,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
        * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $regole = [
            'oggetto' => ['required'],
            'messaggio' => ['required'],
        ];
        if ($authUser->hasPermissionTo('admin')) {
            $regole['destinatario_tipo'] = ['required_without:servizio_type', Rule::in(['agente', 'supervisore'])];
            $regole['destinatario_id'] = ['required_without:servizio_type', 'nullable', 'integer', 'exists:users,id'];
        } else {
            $regole['servizio_type'] = ['required'];
        }

        $request->validate($regole);

        if ($authUser->hasPermissionTo('admin') && $request->filled('destinatario_id') && $request->filled('destinatario_tipo')) {
            $destinatarioValido = User::query()
                ->where('id', $request->input('destinatario_id'))
                ->whereHas('permissions', function ($query) use ($request) {
                    $query->where('name', $request->input('destinatario_tipo'));
                })
                ->exists();

            if (!$destinatarioValido) {
                return back()
                    ->withErrors(['destinatario_id' => 'Il destinatario selezionato non è valido per il tipo scelto.'])
                    ->withInput();
            }
        }

        $ticket = new Ticket();
        $ticket->servizio_id = $request->input('servizio_id');
        $ticket->servizio_type = $request->input('servizio_type');

        $ticket->user_id = Auth::id();

        $destinatarioTipo = $request->input('destinatario_tipo');
        $destinatarioId = $request->input('destinatario_id');

        if ($authUser->hasPermissionTo('agente')) {
            $ticket->agente_id = Auth::id();
        } else {
            if ($authUser->hasPermissionTo('admin') && $destinatarioId) {
                $ticket->agente_id = $destinatarioId;
            } else {
                $ticket->agente_id = $ticket->servizio?->agente_id;
            }
        }

        $ticket->oggetto = $request->input('oggetto');
        $ticket->stato = 'aperto';
        $ticket->causale_ticket_id = $request->input('causale_ticket_id');
        $ticket->uid = $request->input('uid');
        $ticket->da_tipo_utente = $this->determinaDaTipoUtente();
        $ticket->a_tipo_utente = $this->determinaATipoUtente($ticket->da_tipo_utente, $destinatarioTipo);
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

        $destinatarioNotificaId = null;
        if ($ticket->da_tipo_utente === 'admin') {
            $destinatarioNotificaId = $ticket->agente_id;
        } else {
            $destinatarioNotificaId = $this->trovaAdminDestinatarioId(Auth::id());
        }

        if ($destinatarioNotificaId && (int)$destinatarioNotificaId !== (int)Auth::id()) {
            $lettura = new LetturaTicket();
            $lettura->ticket_id = $ticket->id;
            $lettura->user_id = $destinatarioNotificaId;
            $lettura->messaggio_letto = 0;
            $lettura->save();
        }


        AllegatoMessaggioTicket::where('uid', $messaggio->uid)->whereNull('messaggio_id')->update(['messaggio_id' => $messaggio->id, 'uid' => null]);

        $authUserNominativo = $authUser->nominativo();
        dispatch(function () use ($ticket, $authUserNominativo, $destinatarioNotificaId) {

            if ($ticket->da_tipo_utente === 'admin') {
                $utente = User::find($destinatarioNotificaId);
                if ($utente) {
                    $utente->notify(new NotificaNuovoTicketAdAdmin($ticket));
                }
            } elseif ($destinatarioNotificaId) {
                Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">' . $ticket->oggetto . '</span> da agente <span class="fw-bold">' . $authUserNominativo . '</span>');
                $utente = User::find($destinatarioNotificaId);
                $utente->notify(new NotificaNuovoTicketAdAdmin($ticket));
            }


        })->afterResponse();

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

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
            'admin' => $authUser->hasPermissionTo('admin'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return never
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
        * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($authUser->hasPermissionTo('admin')) {

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
     * @return never
     */
    public function destroy($id)
    {
        abort(404);
    }


    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    protected function determinaDaTipoUtente()
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($authUser->hasPermissionTo('admin')) {
            return 'admin';
        } elseif ($authUser->hasAnyPermission(['agente', 'supervisore'])) {
            return 'agente';
        }
    }


    protected function determinaATipoUtente($daTipoUtente, ?string $destinatarioTipo = null)
    {
        return $daTipoUtente == 'admin' ? ($destinatarioTipo ?: 'agente') : 'admin';
    }

    protected function trovaAdminDestinatarioId(?int $escludiId = null): ?int
    {
        return User::query()
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'admin');
            })
            ->when($escludiId, function ($query) use ($escludiId) {
                $query->where('id', '<>', $escludiId);
            })
            ->value('id');
    }

}

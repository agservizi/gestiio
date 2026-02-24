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
use App\Models\ContrattoEnergia;
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

        $assegnatariFiltro = collect();
        if ($authUser->hasAnyPermission(['admin', 'supervisore'])) {
            $assegnatariFiltro = User::query()
                ->where(function ($query) {
                    $query->whereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', ['agente', 'supervisore', 'operatore']);
                    });
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);
        }

        return view('Backend.Tickets.index')->with([
            'records' => $records,
            'filtro' => false,
            'controller' => get_class($this),
            'titoloPagina' => ucfirst(Ticket::NOME_PLURALE),
            'admin' => $authUser->hasPermissionTo('admin'),
            'conFiltro' => $this->conFiltro,
            'assegnatariFiltro' => $assegnatariFiltro,

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
            ->with('assegnatario:id,nome,cognome')
            ->with('causaleTicket:id,descrizione_causale')
            ->with('lettura')
            ->orderByDesc('id');

        /** @var User $authUser */
        $authUser = Auth::user();
        if ($authUser->hasAnyPermission(['agente', 'operatore'])) {
            $queryBuilder->where('agente_id', Auth::id());
        }

        if ($request->filled('assegnatario_id')) {
            if ($request->input('assegnatario_id') === '__non_assegnato') {
                $queryBuilder->whereNull('agente_id');
            } else {
                $queryBuilder->where('agente_id', $request->integer('assegnatario_id'));
            }
            $this->conFiltro = true;
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
        $defaultDestinatarioTipo = null;
        $defaultDestinatarioId = null;

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
                case 'contratto-energia':
                    $servizio = ContrattoEnergia::find($request->input('servizio_id'));
                    $record->servizio_type = ContrattoEnergia::class;
                    break;

            }

            if ($servizio) {
                $record->servizio()->associate($servizio);
                $record->oggetto = $this->buildDefaultOggettoFromServizio($request->input('servizio_type'), $servizio);

                if (
                    $authUser->hasPermissionTo('admin')
                    && in_array($request->input('servizio_type'), ['contratto-energia', 'contratto-telefonia'], true)
                    && (
                        $servizio instanceof ContrattoEnergia
                        || $servizio instanceof ContrattoTelefonia
                    )
                    && $servizio->agente_id
                ) {
                    $defaultDestinatarioTipo = 'agente';
                    $defaultDestinatarioId = (int)$servizio->agente_id;
                }
            }
        }

        $agentiDestinatari = collect();
        $supervisoriDestinatari = collect();
        $operatoriDestinatari = collect();
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

            $operatoriDestinatari = User::query()
                ->whereHas('permissions', function ($query) {
                    $query->where('name', 'operatore');
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
            'operatoriDestinatari' => $operatoriDestinatari,
            'defaultDestinatarioTipo' => $defaultDestinatarioTipo,
            'defaultDestinatarioId' => $defaultDestinatarioId,
        ]);
    }

    protected function buildDefaultOggettoFromServizio(string $servizioType, $servizio): string
    {
        if ($servizioType === 'contratto-energia' && $servizio instanceof ContrattoEnergia) {
            $codiceInterno = $servizio->codice_contratto_interno ?: ('OP' . str_pad((string)$servizio->id, 11, '0', STR_PAD_LEFT));
            $gestore = $servizio->gestore?->nome ?: 'N/D';
            $cliente = trim((string)($servizio->denominazione ?: $servizio->nominativo()));
            $documento = trim((string)($servizio->codice_fiscale ?: $servizio->partita_iva ?: ''));

            $parts = [
                'Ticket Contratto Energia',
                'Gestore: ' . $gestore,
                'Cod. interno: ' . $codiceInterno,
                'Cod. esterno: ' . ($servizio->codice_contratto ?: 'N/D'),
            ];

            if ($cliente !== '') {
                $parts[] = 'Cliente: ' . $cliente;
            }

            if ($documento !== '') {
                $parts[] = 'CF/P.IVA: ' . $documento;
            }

            return implode(' | ', $parts);
        }

        if ($servizioType === 'contratto-telefonia' && $servizio instanceof ContrattoTelefonia) {
            $codiceInterno = $servizio->codice_contratto_interno ?: ('TEL' . str_pad((string)$servizio->id, 11, '0', STR_PAD_LEFT));
            $tipoContratto = $servizio->tipoContratto?->nome ?: 'N/D';
            $cliente = trim((string)$servizio->nominativo());
            $documento = trim((string)($servizio->codice_fiscale ?: $servizio->partita_iva ?: ''));

            $parts = [
                'Ticket Contratto Telefonia',
                'Tipo: ' . $tipoContratto,
                'Cod. interno: ' . $codiceInterno,
                'Cod. esterno: ' . ($servizio->codice_contratto ?: 'N/D'),
            ];

            if ($cliente !== '') {
                $parts[] = 'Cliente: ' . $cliente;
            }

            if ($documento !== '') {
                $parts[] = 'CF/P.IVA: ' . $documento;
            }

            return implode(' | ', $parts);
        }

        return (string)($servizio->oggetto ?? '');
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
            $regole['destinatario_tipo'] = ['required_without:servizio_type', Rule::in(['agente', 'supervisore', 'operatore'])];
            $regole['destinatario_id'] = ['required_without:servizio_type', 'nullable', 'integer', 'exists:users,id'];
        } else {
            $regole['servizio_type'] = ['nullable'];
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
        $isTicketServizioContratto = in_array($request->input('servizio_type'), ['contratto-energia', 'contratto-telefonia'], true);

        if (!$authUser->hasPermissionTo('admin') && $isTicketServizioContratto) {
            // Ticket contratto (energia/telefonia) aperto da agente/operatore/supervisore: destinatario sempre admin.
            $destinatarioTipo = 'admin';
            $destinatarioId = $this->trovaAdminDestinatarioId(Auth::id());
        }

        if ($authUser->hasAnyPermission(['agente', 'supervisore', 'operatore'])) {
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
                Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">' . $ticket->oggetto . '</span> da ' . $ticket->da_tipo_utente . ' <span class="fw-bold">' . $authUserNominativo . '</span>');
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
            ->with('assegnatario:id,nome,cognome')
            ->find($id);

        abort_if(!$record, 404, 'Questo ticket non esiste');
        if ($authUser->hasAnyPermission(['agente', 'operatore'])) {
            abort_if((int)$record->agente_id !== (int)Auth::id() && (int)$record->user_id !== (int)Auth::id(), 403, 'Non autorizzato ad accedere a questo ticket');
        }
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

        $assegnatariDestinatari = collect();
        $canGestireAssegnazione = $authUser->hasAnyPermission(['admin', 'supervisore']);
        if ($canGestireAssegnazione) {
            $assegnatariDestinatari = User::query()
                ->where(function ($query) {
                    $query->whereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->where('name', 'agente');
                    })->orWhereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->where('name', 'supervisore');
                    })->orWhereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->where('name', 'operatore');
                    });
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);
        }


        return view('Backend.Tickets.show', [
            'controller' => get_class($this),
            'record' => $record,
            'titoloPagina' => $record->oggetto,
            'admin' => $authUser->hasPermissionTo('admin'),
            'canGestireAssegnazione' => $canGestireAssegnazione,
            'assegnatariDestinatari' => $assegnatariDestinatari,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return void
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
        $canGestireStato = $authUser->hasAnyPermission(['admin', 'supervisore']);
        $canGestireAssegnazione = $authUser->hasAnyPermission(['admin', 'supervisore']);

        if ($canGestireStato) {

        } else {
            $request->validate([
                'messaggio' => ['required']
            ]);

        }

        $ticket = Ticket::find($id);
        abort_if(!$ticket, 404, 'Questo ' . Ticket::NOME_SINGOLARE . ' non esiste');
        if ($authUser->hasAnyPermission(['agente', 'operatore'])) {
            abort_if((int)$ticket->agente_id !== (int)Auth::id() && (int)$ticket->user_id !== (int)Auth::id(), 403, 'Non autorizzato ad aggiornare questo ticket');
        }

        if ($request->filled('agente_id')) {
            abort_unless($canGestireAssegnazione, 403, 'Non autorizzato a riassegnare il ticket');

            $request->validate([
                'agente_id' => [
                    'required',
                    'integer',
                    'exists:users,id',
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->whereHas('permissions', function ($permissionQuery) {
                            $permissionQuery->whereIn('name', ['agente', 'supervisore', 'operatore']);
                        });
                    }),
                ],
            ]);

            $ticket->agente_id = $request->integer('agente_id');
            $ticket->save();

            if ((int)$ticket->agente_id !== (int)Auth::id()) {
                LetturaTicket::updateOrCreate(
                    ['ticket_id' => $ticket->id, 'user_id' => $ticket->agente_id],
                    ['messaggio_letto' => 0]
                );
            }
        }

        if ($request->input('stato')) {
            abort_unless($canGestireStato, 403, 'Non autorizzato a modificare lo stato del ticket');
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
     * @return void
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
        } elseif ($authUser->hasPermissionTo('supervisore')) {
            return 'supervisore';
        } elseif ($authUser->hasPermissionTo('operatore')) {
            return 'operatore';
        } elseif ($authUser->hasPermissionTo('agente')) {
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

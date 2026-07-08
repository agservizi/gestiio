<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AllegatoMessaggioTicket;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\LetturaTicket;
use App\Models\MessaggioTicket;
use App\Models\Notifica;
use App\Models\SpedizioneBrt;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Notifications\NotificaAggiornamentoTicketAUtente;
use App\Notifications\NotificaLetturaTicket;
use App\Notifications\NotificaNuovoTicketAdAdmin;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketsController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $canUseKanban = $authUser->hasAnyPermission(['admin', 'supervisore', 'operatore']);
        $currentView = $request->input('view', 'lista');
        if (! $canUseKanban) {
            $currentView = 'lista';
        }

        $baseQuery = $this->applicaFiltri($request);
        $records = (clone $baseQuery)->paginate();
        $records->appends($request->query());

        $now = now();
        $slaLimit = $now->copy()->addHours(2);

        $metriche = [
            'totale' => (clone $baseQuery)->count(),
            'non_assegnati' => (clone $baseQuery)->whereNull('agente_id')->count(),
            'in_carico_a_me' => (clone $baseQuery)->where('agente_id', Auth::id())->count(),
            'nuovi_da_leggere' => (clone $baseQuery)->whereHas('lettura', fn ($q) => $q->where('user_id', Auth::id())->where('messaggio_letto', 0))->count(),
            'sla_violato' => (clone $baseQuery)->where(function ($q) use ($now) {
                $q->where(function ($inner) use ($now) {
                    $inner->whereNull('first_response_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<', $now);
                })->orWhere(function ($inner) use ($now) {
                    $inner->whereNotIn('stato', ['risolto', 'chiuso'])
                        ->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<', $now);
                });
            })->count(),
            'sla_in_scadenza' => (clone $baseQuery)->where(function ($q) use ($now, $slaLimit) {
                $q->where(function ($inner) use ($now, $slaLimit) {
                    $inner->whereNull('first_response_at')
                        ->whereNotNull('first_response_due_at')
                        ->whereBetween('first_response_due_at', [$now, $slaLimit]);
                })->orWhere(function ($inner) use ($now, $slaLimit) {
                    $inner->whereNotIn('stato', ['risolto', 'chiuso'])
                        ->whereNotNull('resolution_due_at')
                        ->whereBetween('resolution_due_at', [$now, $slaLimit]);
                });
            })->count(),
            'risolti_oggi' => (clone $baseQuery)->whereDate('resolved_at', $now)->count(),
            'carico_team' => (clone $baseQuery)
                ->reorder()
                ->join('users', 'tickets.agente_id', '=', 'users.id')
                ->selectRaw("CONCAT(users.cognome, ' ', users.nome) as nominativo, COUNT(*) as cnt")
                ->groupBy('nominativo')
                ->orderByDesc('cnt')
                ->limit(8)
                ->pluck('cnt', 'nominativo'),
        ];

        $kanbanRecords = $canUseKanban && $currentView === 'kanban'
            ? (clone $baseQuery)->limit(200)->get()
            : collect();

        $kanbanColumns = collect(Ticket::STATI_TICKETS)->map(function ($config, $stato) use ($kanbanRecords) {
            $items = $kanbanRecords
                ->where('stato', $stato)
                ->sortByDesc(fn (Ticket $ticket) => $ticket->workloadScore())
                ->values();

            return [
                'key' => $stato,
                'label' => $config['testo'],
                'color' => $config['colore'],
                'items' => $items,
                'count' => $items->count(),
            ];
        })->values();

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
            'metriche' => $metriche,
            'kanbanColumns' => $kanbanColumns,
            'currentView' => $currentView,
            'canUseKanban' => $canUseKanban,
            'isAgentView' => $authUser->hasPermissionTo('agente'),

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {
        $queryBuilder = Ticket::query()
            ->with('utente')
            ->with('assegnatario:id,nome,cognome')
            ->with('causaleTicket:id,descrizione_causale')
            ->with('lettura')
            ->orderByRaw("CASE priorita WHEN 'urgente' THEN 4 WHEN 'alta' THEN 3 WHEN 'media' THEN 2 ELSE 1 END DESC")
            ->orderByRaw('CASE WHEN resolution_due_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('resolution_due_at')
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

        if ($request->filled('priorita')) {
            $queryBuilder->where('priorita', $request->input('priorita'));
            $this->conFiltro = true;
        }

        if ($request->filled('owner_team')) {
            $queryBuilder->where('owner_team', $request->input('owner_team'));
            $this->conFiltro = true;
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $queryBuilder->where(function ($query) use ($search) {
                $query->where('oggetto', 'like', '%'.$search.'%')
                    ->orWhere('uid', 'like', '%'.$search.'%')
                    ->orWhereHas('utente', function ($utenteQuery) use ($search) {
                        $utenteQuery->where('nome', 'like', '%'.$search.'%')
                            ->orWhere('cognome', 'like', '%'.$search.'%');
                    });
            });
            $this->conFiltro = true;
        }

        if ($request->filled('sla')) {
            $sla = $request->input('sla');
            if ($sla === 'violato') {
                $queryBuilder->where(function ($query) {
                    $query->where(function ($innerQuery) {
                        $innerQuery->whereNull('first_response_at')
                            ->whereNotNull('first_response_due_at')
                            ->where('first_response_due_at', '<', now());
                    })->orWhere(function ($innerQuery) {
                        $innerQuery->whereNotIn('stato', ['risolto', 'chiuso'])
                            ->whereNotNull('resolution_due_at')
                            ->where('resolution_due_at', '<', now());
                    });
                });
                $this->conFiltro = true;
            } elseif ($sla === 'in_scadenza') {
                $limit = now()->addHours(2);
                $queryBuilder->where(function ($query) use ($limit) {
                    $query->where(function ($innerQuery) use ($limit) {
                        $innerQuery->whereNull('first_response_at')
                            ->whereNotNull('first_response_due_at')
                            ->whereBetween('first_response_due_at', [now(), $limit]);
                    })->orWhere(function ($innerQuery) use ($limit) {
                        $innerQuery->whereNotIn('stato', ['risolto', 'chiuso'])
                            ->whereNotNull('resolution_due_at')
                            ->whereBetween('resolution_due_at', [now(), $limit]);
                    });
                });
                $this->conFiltro = true;
            }
        }

        return $queryBuilder;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(Request $request)
    {

        /** @var User $authUser */
        $authUser = Auth::user();

        $record = new Ticket;
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
                    $defaultDestinatarioId = (int) $servizio->agente_id;
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
            'titoloPagina' => 'Nuovo '.Ticket::NOME_SINGOLARE,
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
            $codiceInterno = $servizio->codice_contratto_interno ?: ('OP'.str_pad((string) $servizio->id, 11, '0', STR_PAD_LEFT));
            $gestore = $servizio->gestore?->nome ?: 'N/D';
            $cliente = trim((string) ($servizio->denominazione ?: $servizio->nominativo()));
            $documento = trim((string) ($servizio->codice_fiscale ?: $servizio->partita_iva ?: ''));

            $parts = [
                'Ticket Contratto Energia',
                'Gestore: '.$gestore,
                'Cod. interno: '.$codiceInterno,
                'Cod. esterno: '.($servizio->codice_contratto ?: 'N/D'),
            ];

            if ($cliente !== '') {
                $parts[] = 'Cliente: '.$cliente;
            }

            if ($documento !== '') {
                $parts[] = 'CF/P.IVA: '.$documento;
            }

            return implode(' | ', $parts);
        }

        if ($servizioType === 'contratto-telefonia' && $servizio instanceof ContrattoTelefonia) {
            $codiceInterno = $servizio->codice_contratto_interno ?: ('TEL'.str_pad((string) $servizio->id, 11, '0', STR_PAD_LEFT));
            $tipoContratto = $servizio->tipoContratto?->nome ?: 'N/D';
            $cliente = trim((string) $servizio->nominativo());
            $documento = trim((string) ($servizio->codice_fiscale ?: $servizio->partita_iva ?: ''));

            $parts = [
                'Ticket Contratto Telefonia',
                'Tipo: '.$tipoContratto,
                'Cod. interno: '.$codiceInterno,
                'Cod. esterno: '.($servizio->codice_contratto ?: 'N/D'),
            ];

            if ($cliente !== '') {
                $parts[] = 'Cliente: '.$cliente;
            }

            if ($documento !== '') {
                $parts[] = 'CF/P.IVA: '.$documento;
            }

            return implode(' | ', $parts);
        }

        return (string) ($servizio->oggetto ?? '');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $regole = [
            'oggetto' => ['required'],
            'messaggio' => ['required'],
            'priorita' => ['nullable', Rule::in(array_keys(Ticket::PRIORITA_TICKETS))],
            'owner_team' => ['nullable', Rule::in(array_keys(Ticket::TEAM_TICKETS))],
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

            if (! $destinatarioValido) {
                return back()
                    ->withErrors(['destinatario_id' => 'Il destinatario selezionato non è valido per il tipo scelto.'])
                    ->withInput();
            }
        }

        $ticket = new Ticket;
        $ticket->servizio_id = $request->input('servizio_id');
        $ticket->servizio_type = $request->input('servizio_type');

        $ticket->user_id = Auth::id();

        $destinatarioTipo = $request->input('destinatario_tipo');
        $destinatarioId = $request->input('destinatario_id');
        $isTicketServizioContratto = in_array($request->input('servizio_type'), ['contratto-energia', 'contratto-telefonia'], true);

        if (! $authUser->hasPermissionTo('admin') && $isTicketServizioContratto) {
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
        $ticket->priorita = $this->determineTicketPriority($request);
        $ticket->owner_team = $this->determineOwnerTeam($request);
        $ticket->stato = $ticket->agente_id ? 'da_prendere' : 'nuovo';
        $ticket->causale_ticket_id = $request->input('causale_ticket_id');
        $ticket->uid = $request->input('uid');
        $ticket->da_tipo_utente = $this->determinaDaTipoUtente();
        $ticket->a_tipo_utente = $this->determinaATipoUtente($ticket->da_tipo_utente, $destinatarioTipo);
        $ticket->automation_notes = $this->buildAutomationNotes($ticket, true);
        $this->applySlaDefaults($ticket);
        $this->syncActivityTimestamps($ticket, true);
        $ticket->save();
        $this->logStatusChange($ticket, null, $ticket->stato, 'Apertura ticket');

        $messaggio = new MessaggioTicket;
        $messaggio->ticket_id = $ticket->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();

        $this->markFirstResponseIfNeeded($ticket, false);
        $this->syncActivityTimestamps($ticket, false);
        $ticket->save();

        $lettura = new LetturaTicket;
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

        if ($destinatarioNotificaId && (int) $destinatarioNotificaId !== (int) Auth::id()) {
            $lettura = new LetturaTicket;
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
                Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">'.$ticket->oggetto.'</span> da '.$ticket->da_tipo_utente.' <span class="fw-bold">'.$authUserNominativo.'</span>');
                $utente = User::find($destinatarioNotificaId);
                $utente->notify(new NotificaNuovoTicketAdAdmin($ticket));
            }

        })->afterResponse();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'ticket_id' => $ticket->id,
                'stato' => $ticket->stato,
                'stato_label' => Ticket::STATI_TICKETS[$ticket->stato]['testo'] ?? $ticket->stato,
                'sla' => $ticket->slaStatus(),
            ]);
        }

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $record = Ticket::with('messaggi.utente')
            ->with('messaggi.allegati')
            ->with('assegnatario:id,nome,cognome')
            ->with('statusLogs.utente:id,nome,cognome')
            ->find($id);

        abort_if(! $record, 404, 'Questo ticket non esiste');
        if ($authUser->hasAnyPermission(['agente', 'operatore'])) {
            abort_if((int) $record->agente_id !== (int) Auth::id() && (int) $record->user_id !== (int) Auth::id(), 403, 'Non autorizzato ad accedere a questo ticket');
        }
        // abort_if(!$record->contratto, 404, 'Questo ticket non esiste');

        dispatch(function () use ($record) {

            $lettura = LetturaTicket::where('ticket_id', $record->id)->where('user_id', Auth::id())->first();
            if ($lettura && ! $lettura->messaggio_letto) {
                $letture = LetturaTicket::where('ticket_id', $record->id)
                    ->where('user_id', '<>', Auth::id())
                    ->pluck('user_id');
                if ($letture->isNotEmpty()) {
                    User::whereIn('id', $letture)->get()->each(function ($utente) use ($record) {
                        $utente->notify(new NotificaLetturaTicket($record));
                    });
                }
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
            'canGestireStato' => $authUser->hasAnyPermission(['admin', 'supervisore', 'operatore']),
            'canGestireAssegnazione' => $canGestireAssegnazione,
            'assegnatariDestinatari' => $assegnatariDestinatari,
            'aiSnapshot' => $record->aiSnapshot(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return void
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, $id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $canGestireStato = $authUser->hasAnyPermission(['admin', 'supervisore', 'operatore']);
        $canGestireAssegnazione = $authUser->hasAnyPermission(['admin', 'supervisore']);
        $canGestireStrategia = $authUser->hasAnyPermission(['admin', 'supervisore']);

        if ($canGestireStato) {
            $request->validate([
                'stato' => ['nullable', Rule::in(array_keys(Ticket::STATI_TICKETS))],
                'priorita' => ['nullable', Rule::in(array_keys(Ticket::PRIORITA_TICKETS))],
                'owner_team' => ['nullable', Rule::in(array_keys(Ticket::TEAM_TICKETS))],
            ]);
        } else {
            $request->validate([
                'messaggio' => ['required'],
            ]);

        }

        $ticket = Ticket::find($id);
        abort_if(! $ticket, 404, 'Questo '.Ticket::NOME_SINGOLARE.' non esiste');
        if ($authUser->hasAnyPermission(['agente', 'operatore'])) {
            abort_if((int) $ticket->agente_id !== (int) Auth::id() && (int) $ticket->user_id !== (int) Auth::id(), 403, 'Non autorizzato ad aggiornare questo ticket');
        }
        $oldState = $ticket->stato;

        if ($request->filled('agente_id')) {
            abort_unless($canGestireAssegnazione, 403, 'Non autorizzato a riassegnare il ticket');

            $request->validate([
                'agente_id' => [
                    'required',
                    'integer',
                    'exists:users,id',
                ],
            ]);

            $assegnatarioValido = User::query()
                ->whereKey($request->integer('agente_id'))
                ->where(function ($query) {
                    $query->whereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', ['agente', 'supervisore', 'operatore']);
                    });
                })
                ->exists();

            if (! $assegnatarioValido) {
                throw ValidationException::withMessages([
                    'agente_id' => 'L\'assegnatario selezionato non è valido.',
                ]);
            }

            $ticket->agente_id = $request->integer('agente_id');
            $ticket->save();

            if ((int) $ticket->agente_id !== (int) Auth::id()) {
                LetturaTicket::updateOrCreate(
                    ['ticket_id' => $ticket->id, 'user_id' => $ticket->agente_id],
                    ['messaggio_letto' => 0]
                );
            }

            if (in_array($ticket->stato, ['nuovo', 'da_prendere'], true)) {
                $ticket->stato = 'in_lavorazione';
            }
        }

        if ($request->input('stato')) {
            abort_unless($canGestireStato, 403, 'Non autorizzato a modificare lo stato del ticket');
            $ticket->stato = $request->input('stato');
        }

        if ($request->filled('priorita')) {
            abort_unless($canGestireStrategia, 403, 'Non autorizzato a modificare la priorita del ticket');
            $ticket->priorita = $request->input('priorita');
        }

        if ($request->filled('owner_team')) {
            abort_unless($canGestireStrategia, 403, 'Non autorizzato a modificare il team del ticket');
            $ticket->owner_team = $request->input('owner_team');
        }

        if ($request->input('messaggio')) {
            $messaggio = new MessaggioTicket;
            $messaggio->ticket_id = $ticket->id;
            $messaggio->user_id = Auth::id();
            $messaggio->messaggio = $request->input('messaggio');
            $messaggio->save();
            $ticket->touch();
            $this->markFirstResponseIfNeeded($ticket, true);
            $this->syncActivityTimestamps($ticket, false);

            LetturaTicket::where('ticket_id', $messaggio->ticket_id)->where('user_id', '<>', Auth::id())->get()->each(function ($record) {
                $record->update(['messaggio_letto' => 0]);
            });

            dispatch(function () use ($ticket) {
                $utente = User::find($ticket->user_id);
                $utente->notify(new NotificaAggiornamentoTicketAUtente($ticket));
            })->afterResponse();

        }

        $this->applySlaDefaults($ticket);
        $this->syncResolutionState($ticket);
        $ticket->automation_notes = $this->buildAutomationNotes($ticket, false);
        $ticket->save();

        if ($oldState !== $ticket->stato) {
            $this->logStatusChange($ticket, $oldState, $ticket->stato, 'Aggiornamento manuale');
        }

        return $this->backToIndex();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
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

    protected function determineTicketPriority(Request $request): string
    {
        $priorita = $request->input('priorita');
        if (is_string($priorita) && array_key_exists($priorita, Ticket::PRIORITA_TICKETS)) {
            return $priorita;
        }

        $oggetto = mb_strtolower((string) $request->input('oggetto'));
        if (str_contains($oggetto, 'blocco') || str_contains($oggetto, 'urgente')) {
            return 'urgente';
        }

        if (str_contains($oggetto, 'errore') || str_contains($oggetto, 'ko')) {
            return 'alta';
        }

        $servizioType = (string) $request->input('servizio_type');
        if (in_array($servizioType, ['contratto-energia', 'contratto-telefonia'], true)) {
            return 'media';
        }

        return 'bassa';
    }

    protected function determineOwnerTeam(Request $request): string
    {
        $ownerTeam = $request->input('owner_team');
        if (is_string($ownerTeam) && array_key_exists($ownerTeam, Ticket::TEAM_TICKETS)) {
            return $ownerTeam;
        }

        $servizioType = (string) $request->input('servizio_type');
        if ($servizioType === 'contratto-energia' || $servizioType === 'contratto-telefonia') {
            return 'commerciale';
        }

        $oggetto = mb_strtolower((string) $request->input('oggetto'));
        if (str_contains($oggetto, 'fattura') || str_contains($oggetto, 'pagamento')) {
            return 'amministrazione';
        }

        return 'helpdesk';
    }

    protected function applySlaDefaults(Ticket $ticket): void
    {
        $priority = $ticket->priorita ?: 'media';

        $firstResponseHours = match ($priority) {
            'urgente' => 1,
            'alta' => 2,
            'media' => 4,
            default => 8,
        };

        $resolutionHours = match ($priority) {
            'urgente' => 8,
            'alta' => 16,
            'media' => 24,
            default => 48,
        };

        if (! $ticket->first_response_due_at) {
            $ticket->first_response_due_at = $ticket->created_at
                ? $ticket->created_at->copy()->addHours($firstResponseHours)
                : now()->addHours($firstResponseHours);
        }

        if (! $ticket->resolution_due_at) {
            $anchor = $ticket->created_at ? $ticket->created_at->copy() : now();
            $ticket->resolution_due_at = $anchor->addHours($resolutionHours);
        }
    }

    protected function markFirstResponseIfNeeded(Ticket $ticket, bool $isReply): void
    {
        if ($ticket->first_response_at) {
            return;
        }

        $isInternalUser = $this->currentUser()?->hasAnyPermission(['admin', 'supervisore', 'operatore']) ?? false;
        if ($isReply && $isInternalUser) {
            $ticket->first_response_at = now();
        }
    }

    protected function syncActivityTimestamps(Ticket $ticket, bool $isCreation): void
    {
        $isInternalUser = $this->currentUser()?->hasAnyPermission(['admin', 'supervisore', 'operatore']) ?? false;
        if ($isCreation && ! $isInternalUser) {
            $ticket->last_customer_message_at = now();

            return;
        }

        if ($isInternalUser) {
            $ticket->last_agent_message_at = now();
        } else {
            $ticket->last_customer_message_at = now();
        }
    }

    protected function syncResolutionState(Ticket $ticket): void
    {
        if ($ticket->stato === 'risolto' && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();

            return;
        }

        if ($ticket->stato === 'chiuso' && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();

            return;
        }

        if (! in_array($ticket->stato, ['risolto', 'chiuso'], true)) {
            $ticket->resolved_at = null;
        }

        if ($ticket->isSlaViolated() && ! $ticket->escalated_at) {
            $ticket->escalated_at = now();
        }
    }

    protected function buildAutomationNotes(Ticket $ticket, bool $isNew): array
    {
        $notes = [];

        if ($isNew) {
            $notes[] = 'Ticket classificato automaticamente su team '.($ticket->owner_team ?: 'helpdesk').'.';
            $notes[] = 'Priorita impostata automaticamente a '.($ticket->priorita ?: 'media').'.';
        }

        if ($ticket->isSlaViolated()) {
            $notes[] = 'SLA superato: attivare escalation o aggiornamento immediato.';
        } elseif ($ticket->isSlaAtRisk()) {
            $notes[] = 'SLA in scadenza: pianificare risposta entro 2 ore.';
        }

        if ($ticket->stato === 'in_attesa_cliente') {
            $notes[] = 'In attesa del cliente: utile programmare un reminder.';
        }

        if ($ticket->stato === 'in_attesa_fornitore') {
            $notes[] = 'In attesa del fornitore: aggiornare il thread appena arriva riscontro.';
        }

        return $notes;
    }

    protected function logStatusChange(Ticket $ticket, ?string $fromState, string $toState, ?string $note = null): void
    {
        TicketStatusLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'note' => $note,
        ]);
    }

    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }
}

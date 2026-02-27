@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <div class="me-2">
            <a href="#" class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder"
               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                Filtri
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                @include('Backend.Tickets.indexFiltri')
            </div>
        </div>
        <a class="btn btn-sm btn-primary" href="{{action([$controller,'create'])}}">Nuovo {{\App\Models\Ticket::NOME_SINGOLARE}}</a>
        <a class="btn btn-sm {{$currentView === 'lista' ? 'btn-light-primary' : 'btn-light'}}" href="{{request()->fullUrlWithQuery(['view' => 'lista'])}}">Vista Lista</a>
        @if($canUseKanban)
            <a class="btn btn-sm {{$currentView === 'kanban' ? 'btn-light-primary' : 'btn-light'}}" href="{{request()->fullUrlWithQuery(['view' => 'kanban'])}}">Vista Kanban</a>
        @endif
        @if($conFiltro)
            <a class="btn btn-sm btn-light-success" href="{{action([$controller,'index'])}}">Reset Filtri</a>
        @endif
    </div>
@endsection

@section('content')
    @php($priorityConfig = \App\Models\Ticket::PRIORITA_TICKETS)
    @php($statusConfig = \App\Models\Ticket::STATI_TICKETS)
    @php($totale = $metriche['totale'] ?? 0)
    @php($nonAssegnati = $metriche['non_assegnati'] ?? 0)
    @php($inCaricoAMe = $metriche['in_carico_a_me'] ?? 0)
    @php($nuoviDaLeggere = $metriche['nuovi_da_leggere'] ?? 0)
    @php($slaViolato = $metriche['sla_violato'] ?? 0)
    @php($slaInScadenza = $metriche['sla_in_scadenza'] ?? 0)
    @php($risoltiOggi = $metriche['risolti_oggi'] ?? 0)

    <div class="card card-flush mb-6 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 48%, #38bdf8 100%);">
        <div class="card-body py-8 px-8 px-lg-12 text-white">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-6">
                <div>
                    <div class="text-uppercase fw-bold opacity-75 fs-8 mb-2">{{$isAgentView ? 'Inbox Ticket' : 'Helpdesk Operativo'}}</div>
                    <h2 class="fw-bolder text-white mb-2">{{$isAgentView ? 'Gestisci rapidamente i ticket assegnati e tieni il cliente aggiornato' : 'Monitora, assegna priorità e risolvi i ticket con un flusso più rapido'}}</h2>
                    <div class="opacity-75 mw-600px">{{$isAgentView ? 'Vista personale focalizzata sui ticket di tua competenza, con stato, SLA e prossime azioni consigliate.' : 'Dashboard centralizzata con vista Kanban, SLA, carico team e suggerimenti operativi per ridurre i tempi di prima risposta e chiusura.'}}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-light text-dark px-4 py-3">{{number_format($totale)}} ticket filtrati</span>
                    <span class="badge badge-light-danger px-4 py-3">{{$slaViolato}} SLA violati</span>
                    <span class="badge badge-light-warning px-4 py-3">{{$slaInScadenza}} in scadenza</span>
                </div>
            </div>
        </div>
    </div>

    @if($isAgentView)
        <div class="card border-0 mb-6" style="background:#ffffff;">
            <div class="card-body p-6">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <div class="rounded p-5 h-100" style="background:#f8fafc;">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Focus immediato</div>
                            <div class="fw-bold fs-4 mb-2">{{$nuoviDaLeggere}} ticket richiedono la tua attenzione</div>
                            <div class="text-muted">Apri prima quelli con SLA in scadenza o con ultimo aggiornamento cliente non gestito.</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="rounded p-5 h-100" style="background:#f8fafc;">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Priorità consigliata</div>
                            <div class="fw-bold fs-4 mb-2">{{$slaViolato + $slaInScadenza}} ticket a rischio SLA</div>
                            <div class="text-muted">Rispondi o aggiorna lo stato per evitare escalation e tempi di chiusura più lunghi.</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="rounded p-5 h-100" style="background:#f8fafc;">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Obiettivo giornata</div>
                            <div class="fw-bold fs-4 mb-2">Chiudi i ticket con esito già definito</div>
                            <div class="text-muted">Usa il dettaglio ticket per rispondere al cliente e passare rapidamente a <strong>Risolto</strong> o <strong>Chiuso</strong>.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-5 mb-6">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0" style="background:#f8fafc;">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7">Nuovi da leggere</div>
                    <div class="fs-2hx fw-bold mt-2 text-danger">{{$nuoviDaLeggere}}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0" style="background:#f8fafc;">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7">In carico a me</div>
                    <div class="fs-2hx fw-bold mt-2 text-primary">{{$inCaricoAMe}}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0" style="background:#f8fafc;">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7">Non assegnati</div>
                    <div class="fs-2hx fw-bold mt-2 text-warning">{{$nonAssegnati}}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0" style="background:#f8fafc;">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7">Risolti oggi</div>
                    <div class="fs-2hx fw-bold mt-2 text-success">{{$risoltiOggi}}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card border-0 h-100" style="background:#ffffff;">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Distribuzione operativo</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-4">
                        @foreach($statusConfig as $key => $status)
                            @php($count = collect($kanbanColumns)->firstWhere('key', $key)['count'] ?? 0)
                            <div class="col-md-6 col-xl-4">
                                <div class="rounded p-4 h-100" style="background: {{$status['coloreHex']}}; border:1px solid rgba(15,23,42,0.06);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="text-muted fs-8 text-uppercase fw-bold">{{$status['testo']}}</div>
                                            <div class="fs-2 fw-bolder mt-2">{{$count}}</div>
                                        </div>
                                        <span class="badge badge-light-{{$status['colore']}}">{{$status['colore']}}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 h-100" style="background:#ffffff;">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Carico Team</h3>
                </div>
                <div class="card-body pt-0">
                    @forelse(($metriche['carico_team'] ?? collect()) as $nome => $conteggio)
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-gray-100">
                            <div class="fw-semibold">{{$nome}}</div>
                            <span class="badge badge-light-primary">{{$conteggio}}</span>
                        </div>
                    @empty
                        <div class="text-muted">Nessun dato disponibile.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div id="ticket-loading-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background: rgba(15,23,42,0.18); z-index: 1055;">
        <div class="d-flex align-items-center justify-content-center w-100 h-100">
            <div class="bg-white rounded px-6 py-4 shadow-sm d-flex align-items-center gap-3">
                <span class="spinner-border spinner-border-sm text-primary"></span>
                <span class="fw-semibold">Operazione in corso...</span>
            </div>
        </div>
    </div>

    @if($currentView === 'kanban')
        <div class="row g-5">
            @foreach($kanbanColumns as $column)
                <div class="col-xl-4 col-xxl">
                    <div class="card border-0 h-100" style="background:#f8fafc;">
                        <div class="card-header border-0 pt-5 pb-3">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div>
                                    <div class="fw-bold">{{$column['label']}}</div>
                                    <div class="text-muted fs-8">{{$column['count']}} ticket</div>
                                </div>
                                <span class="badge badge-light-{{$column['color']}} js-kanban-count">{{$column['count']}}</span>
                            </div>
                        </div>
                        <div class="card-body pt-0 js-kanban-column"
                             data-status="{{$column['key']}}"
                             data-status-label="{{$column['label']}}"
                             data-status-color="{{$column['color']}}">
                            @forelse($column['items'] as $ticket)
                                @php($sla = $ticket->slaStatus())
                                @php($ai = $ticket->aiSnapshot())
                                <div class="rounded p-4 mb-4 bg-white border border-gray-200 shadow-sm js-kanban-card"
                                     draggable="true"
                                     data-ticket-id="{{$ticket->id}}"
                                     data-current-status="{{$ticket->stato}}">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <a href="{{action([$controller,'show'],$ticket->id)}}" class="fw-bold text-gray-900 text-hover-primary">{{$ticket->uidTicket()}}</a>
                                            <div class="text-muted fs-8 mt-1">{{$ticket->oggetto}}</div>
                                        </div>
                                        {!! $ticket->labelPrioritaTicket() !!}
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        {!! $ticket->labelStatoTicket() !!}
                                        <span class="badge badge-light-{{$sla['classe']}} js-kanban-sla">{{$sla['testo']}}</span>
                                    </div>
                                    @if(Auth::user()?->hasAnyPermission(['admin','supervisore']))
                                        <div class="mb-3">
                                            <label class="form-label fs-8 text-muted text-uppercase fw-bold mb-1">Priorità</label>
                                            <select class="form-select form-select-sm form-select-solid js-kanban-priority"
                                                    data-ticket-id="{{$ticket->id}}">
                                                @foreach(\App\Models\Ticket::PRIORITA_TICKETS as $priorityKey => $priorityValue)
                                                    <option value="{{$priorityKey}}" {{$ticket->priorita === $priorityKey ? 'selected' : ''}}>
                                                        {{$priorityValue['testo']}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="text-muted fs-8 mb-3">{{$ai['next_action']}}</div>
                                    <div class="d-flex justify-content-between align-items-center fs-8 text-muted mb-3">
                                        <span>{{$ticket->assegnatario?->nominativo() ?? 'Non assegnato'}}</span>
                                        <span>{{$ticket->updated_at->diffForHumans()}}</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{action([$controller,'show'],$ticket->id)}}" class="btn btn-sm btn-light-primary flex-grow-1">Apri</a>
                                        @if(Auth::user()?->hasAnyPermission(['admin','supervisore','operatore']))
                                            <form method="POST" action="{{action([$controller,'update'],$ticket->id)}}" class="flex-grow-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="stato" value="{{in_array($ticket->stato, ['nuovo','da_prendere'], true) ? 'in_lavorazione' : 'risolto'}}">
                                                <button type="submit" class="btn btn-sm btn-light-success w-100">{{in_array($ticket->stato, ['nuovo','da_prendere'], true) ? 'Prendi' : 'Risolvi'}}</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded p-6 text-center bg-white border border-dashed border-gray-300 text-muted">Nessun ticket in questa colonna.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card border-0">
            <div class="card-header border-0 py-5 d-flex flex-wrap justify-content-between gap-4">
                <div>
                    <h3 class="card-title fw-bold mb-0">Queue Helpdesk</h3>
                    <div class="text-muted fs-8 mt-1">Gestione rapida con stato, priorità e scadenze SLA.</div>
                </div>
                <div class="w-300px max-w-100">
                    <input id="js-ticket-local-search" type="text" class="form-control form-control-solid" placeholder="Cerca nei ticket in pagina...">
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table id="js-ticket-table" class="table table-row-dashed align-middle gy-4">
                        <thead>
                        <tr class="fw-bolder fs-7 text-uppercase text-muted">
                            <th>#</th>
                            <th>Oggetto</th>
                            <th>Priorità</th>
                            <th>Team</th>
                            <th>Assegnato a</th>
                            <th>SLA</th>
                            <th>Stato</th>
                            <th>Ultimo update</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $record)
                            @php($sla = $record->slaStatus())
                            @php($ai = $record->aiSnapshot())
                            <tr>
                                <td class="fw-bold">{{$record->uidTicket()}}</td>
                                <td>
                                    <div class="fw-semibold text-gray-900">{{$record->oggetto}}</div>
                                    <div class="text-muted fs-8 mt-1">{{$record->causaleTicket?->descrizione_causale ?? 'Senza causale'}} | {{$record->classeServizio() ?: 'Generico'}}</div>
                                    <div class="text-muted fs-8">{{$ai['next_action']}}</div>
                                </td>
                                <td>{!! $record->labelPrioritaTicket() !!}</td>
                                <td><span class="badge badge-light">{{\App\Models\Ticket::TEAM_TICKETS[$record->owner_team] ?? ucfirst((string)$record->owner_team)}}</span></td>
                                <td>
                                    {{$record->assegnatario?->nominativo() ?? 'Non assegnato'}}
                                    @if(!$record->agente_id)
                                        <span class="badge badge-light-warning ms-1">Da assegnare</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light-{{$sla['classe']}}">{{$sla['testo']}}</span>
                                    <div class="text-muted fs-8 mt-1">{{$record->slaSummary()}}</div>
                                </td>
                                <td>{!! $record->labelStatoTicket() !!}</td>
                                <td>{{$record->updated_at->format('d/m/Y H:i')}}</td>
                                <td class="text-end" style="white-space: nowrap;">
                                    <a class="btn btn-sm btn-light-primary" href="{{action([$controller,'show'],$record->id)}}">Apri</a>
                                    @if(Auth::user()?->hasAnyPermission(['admin','supervisore']) && $assegnatariFiltro->count())
                                        <form method="POST" action="{{action([$controller,'update'],$record->id)}}" class="d-inline-flex align-items-center ms-1 js-ticket-assign-form">
                                            @csrf
                                            @method('PATCH')
                                            <select name="agente_id" class="form-select form-select-sm form-select-solid me-1" style="width: 160px;" required>
                                                <option value="">Assegna...</option>
                                                @foreach($assegnatariFiltro as $assegnatario)
                                                    <option value="{{$assegnatario->id}}" {{(int)($record->agente_id ?? 0) === (int)$assegnatario->id ? 'selected' : ''}}>{{$assegnatario->cognome}} {{$assegnatario->nome}}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-light">OK</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-muted">Nessun ticket trovato.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-4">{{$records->links()}}</div>
            </div>
        </div>
    @endif
@endsection

@push('customScript')
    <script>
        $(function () {
            const overlay = $('#ticket-loading-overlay');
            const csrfToken = $('meta[name="_token"]').attr('content');
            let draggedCard = null;

            $('#js-ticket-local-search').on('input', function () {
                const query = ($(this).val() || '').toLowerCase().trim();
                $('#js-ticket-table tbody tr').each(function () {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(query === '' || text.indexOf(query) !== -1);
                });
            });

            $(document).on('submit', '.js-ticket-assign-form, .js-ticket-takeover-form', function () {
                overlay.removeClass('d-none');
            });

            function refreshKanbanCount(column) {
                const count = column.find('.js-kanban-card').length;
                column.closest('.card').find('.js-kanban-count').text(count);
                column.closest('.card').find('.text-muted.fs-8').first().text(count + ' ticket');
            }

            function updateTicketStatus(ticketId, status, onSuccess, onError) {
                $.ajax({
                    url: "{{action([$controller,'index'])}}/" + ticketId,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    data: {
                        _method: 'PATCH',
                        stato: status
                    },
                    success: function (response) {
                        if (typeof onSuccess === 'function') {
                            onSuccess(response);
                        }
                    },
                    error: function () {
                        if (typeof onError === 'function') {
                            onError();
                        }
                    }
                });
            }

            function updateTicketPriority(ticketId, priority, onSuccess, onError) {
                $.ajax({
                    url: "{{action([$controller,'index'])}}/" + ticketId,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    data: {
                        _method: 'PATCH',
                        priorita: priority
                    },
                    success: function (response) {
                        if (typeof onSuccess === 'function') {
                            onSuccess(response);
                        }
                    },
                    error: function () {
                        if (typeof onError === 'function') {
                            onError();
                        }
                    }
                });
            }

            function priorityBadgeClass(priority) {
                const map = {
                    bassa: 'light',
                    media: 'info',
                    alta: 'warning',
                    urgente: 'danger'
                };

                return map[priority] || 'light';
            }

            $(document).on('dragstart', '.js-kanban-card', function (event) {
                draggedCard = this;
                $(this).addClass('opacity-50');
                event.originalEvent.dataTransfer.effectAllowed = 'move';
                event.originalEvent.dataTransfer.setData('text/plain', $(this).data('ticket-id'));
            });

            $(document).on('dragend', '.js-kanban-card', function () {
                $(this).removeClass('opacity-50');
            });

            $(document).on('dragover', '.js-kanban-column', function (event) {
                event.preventDefault();
                event.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('bg-light-primary');
            });

            $(document).on('dragleave', '.js-kanban-column', function () {
                $(this).removeClass('bg-light-primary');
            });

            $(document).on('drop', '.js-kanban-column', function (event) {
                event.preventDefault();
                $(this).removeClass('bg-light-primary');

                if (!draggedCard) {
                    return;
                }

                const targetColumn = $(this);
                const newStatus = targetColumn.data('status');
                const card = $(draggedCard);
                const oldStatus = card.attr('data-current-status');
                if (newStatus === oldStatus) {
                    return;
                }

                const sourceColumn = card.closest('.js-kanban-column');
                const ticketId = card.data('ticket-id');

                overlay.removeClass('d-none');
                targetColumn.prepend(card);
                refreshKanbanCount(sourceColumn);
                refreshKanbanCount(targetColumn);

                updateTicketStatus(ticketId, newStatus, function (response) {
                    card.attr('data-current-status', response.stato);
                    const stateBadge = card.find('.badge').first();
                    const color = targetColumn.data('status-color');
                    const label = targetColumn.data('status-label');
                    stateBadge
                        .attr('class', 'badge badge-' + color + ' fw-bolder me-2')
                        .text(label);
                    overlay.addClass('d-none');
                }, function () {
                    sourceColumn.prepend(card);
                    refreshKanbanCount(sourceColumn);
                    refreshKanbanCount(targetColumn);
                    overlay.addClass('d-none');
                    Swal.fire('Errore', 'Non è stato possibile aggiornare lo stato del ticket.', 'error');
                });
            });

            $(document).on('focus', '.js-kanban-priority', function () {
                $(this).data('previous', $(this).val());
            });

            $(document).on('change', '.js-kanban-priority', function () {
                const select = $(this);
                const ticketId = select.data('ticket-id');
                const newPriority = select.val();
                const previous = select.data('previous');
                const card = select.closest('.js-kanban-card');
                const priorityBadge = card.find('.d-flex.justify-content-between.align-items-start .badge').last();

                overlay.removeClass('d-none');
                updateTicketPriority(ticketId, newPriority, function () {
                    const label = select.find('option:selected').text().trim();
                    priorityBadge
                        .attr('class', 'badge badge-light-' + priorityBadgeClass(newPriority) + ' fw-bolder')
                        .text(label);
                    overlay.addClass('d-none');
                    select.data('previous', newPriority);
                }, function () {
                    select.val(previous);
                    overlay.addClass('d-none');
                    Swal.fire('Errore', 'Non è stato possibile aggiornare la priorità del ticket.', 'error');
                });
            });
        });
    </script>
@endpush

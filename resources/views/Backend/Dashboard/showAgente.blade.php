@php
    $container = 'container-xxl';
@endphp
@extends('Backend._layout._main')

@section('toolbar', '')
@section('content')
    @php
        $heroOperativo = $heroOperativo ?? [
            'ticket_aperti_miei' => 0,
            'pratiche_ferme' => 0,
            'attivita_oggi' => 0,
        ];
        $filtriGlobali = $filtriGlobali ?? ['periodo' => '7d', 'priorita' => '', 'stato' => 'aperto', 'cliente' => ''];
        $ticketDaPrendereInCarico = $ticketDaPrendereInCarico ?? collect();
        $visureInAttesaDocumenti = $visureInAttesaDocumenti ?? collect();
        $cafInAttesaDocumenti = $cafInAttesaDocumenti ?? collect();
        $scadenzeOggi = $scadenzeOggi ?? collect();
        $monitorOperativo = $monitorOperativo ?? [
            'trend_7d' => 0,
            'trend_30d' => 0,
            'pratiche_attenzione' => 0,
            'ferme_oltre_x_giorni' => 0,
            'tempo_medio_risposta_ore' => 0,
            'soglia_rossa' => 10,
            'soglia_gialla' => 5,
        ];
        $timelineAttivita = $timelineAttivita ?? collect();
        $inAttesaDocumenti = $visureInAttesaDocumenti->map(function($r){
            return [
                'id' => $r->id,
                'record_type' => 'visura',
                'tipo' => 'Visura',
                'cliente' => $r->nominativo(),
                'eta_giorni' => $r->created_at ? $r->created_at->diffInDays(now()) : 0,
                'open_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
                'assign_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
                'complete_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
            ];
        })->merge($cafInAttesaDocumenti->map(function($r){
            return [
                'id' => $r->id,
                'record_type' => 'caf',
                'tipo' => 'CAF',
                'cliente' => $r->nominativo(),
                'eta_giorni' => $r->created_at ? $r->created_at->diffInDays(now()) : 0,
                'open_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
                'assign_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
                'complete_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
            ];
        }));
    @endphp
    <div class="position-sticky top-0" style="z-index: 10;">
        <div class="card mb-6">
            <div class="card-body py-5">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 class="mb-1">Cose da fare ora</h2>
                        <div class="text-muted">Pannello operativo personale</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}" class="btn btn-sm btn-primary">Apri ticket</a>
                        <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}" class="btn btn-sm btn-light-primary">Apri visure</a>
                        <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}" class="btn btn-sm btn-light-primary">Apri CAF</a>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="border border-dashed rounded p-4 h-100">
                            <div class="text-muted fs-7">Ticket aperti miei</div>
                            <div class="fs-2hx fw-bold text-primary">{{number_format($heroOperativo['ticket_aperti_miei'])}}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border border-dashed rounded p-4 h-100">
                            <div class="text-muted fs-7">Pratiche in attenzione</div>
                            <div class="fs-2hx fw-bold {{ $heroOperativo['pratiche_ferme'] >= $monitorOperativo['soglia_rossa'] ? 'text-danger' : ($heroOperativo['pratiche_ferme'] >= $monitorOperativo['soglia_gialla'] ? 'text-warning' : 'text-primary') }}">{{number_format($heroOperativo['pratiche_ferme'])}}</div>
                            <div class="text-muted fs-8">Ferme da &gt; 3 giorni</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border border-dashed rounded p-4 h-100">
                            <div class="text-muted fs-7">Attività oggi</div>
                            <div class="fs-2hx fw-bold text-success">{{number_format($heroOperativo['attivita_oggi'])}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body py-4">
            <form method="GET" action="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Periodo</label>
                    <select class="form-select form-select-sm form-select-solid" name="periodo">
                        <option value="oggi" {{$filtriGlobali['periodo']==='oggi'?'selected':''}}>Oggi</option>
                        <option value="7d" {{$filtriGlobali['periodo']==='7d'?'selected':''}}>Ultimi 7 giorni</option>
                        <option value="30d" {{$filtriGlobali['periodo']==='30d'?'selected':''}}>Ultimi 30 giorni</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Priorità</label>
                    <select class="form-select form-select-sm form-select-solid" name="priorita">
                        <option value="" {{$filtriGlobali['priorita']===''?'selected':''}}>Tutte</option>
                        <option value="alta" {{$filtriGlobali['priorita']==='alta'?'selected':''}}>Alta</option>
                        <option value="media" {{$filtriGlobali['priorita']==='media'?'selected':''}}>Media</option>
                        <option value="bassa" {{$filtriGlobali['priorita']==='bassa'?'selected':''}}>Bassa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Stato</label>
                    <select class="form-select form-select-sm form-select-solid" name="stato">
                        <option value="aperto" {{$filtriGlobali['stato']==='aperto'?'selected':''}}>Aperti</option>
                        <option value="chiuso" {{$filtriGlobali['stato']==='chiuso'?'selected':''}}>Chiuse</option>
                        <option value="tutti" {{$filtriGlobali['stato']==='tutti'?'selected':''}}>Tutte</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cliente / ricerca</label>
                    <input type="text" class="form-control form-control-sm form-control-solid" name="cliente" value="{{$filtriGlobali['cliente']}}"
                           placeholder="Nome, P.IVA, CF, oggetto ticket">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Applica</button>
                    <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}" class="btn btn-sm btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-xl-8">
            <div class="card mb-6">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">Da prendere in carico</h3>
                    <div class="card-toolbar d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="open" data-bulk-target="#queue-ticket">Apri</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="assign" data-bulk-target="#queue-ticket">Assegna</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="complete" data-bulk-target="#queue-ticket">Completa</button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if($ticketDaPrendereInCarico->isEmpty())
                        <div class="text-muted">Nessun ticket nel filtro corrente.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle" id="queue-ticket">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th style="width:30px"><input type="checkbox" class="form-check-input bulk-check-all"></th>
                                    <th>Ticket</th>
                                    <th>Stato</th>
                                    <th>Età</th>
                                    <th>Priorità</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($ticketDaPrendereInCarico as $ticket)
                                    @php($etaGiorni = $ticket->created_at ? $ticket->created_at->diffInDays(now()) : 0)
                                    @php($priorita = $etaGiorni >= 3 ? 'Alta' : ($etaGiorni >= 1 ? 'Media' : 'Bassa'))
                                    @php($prioritaClass = $etaGiorni >= 3 ? 'badge-light-danger' : ($etaGiorni >= 1 ? 'badge-light-warning' : 'badge-light-success'))
                                    @php($openUrl = action([\App\Http\Controllers\Backend\TicketsController::class,'show'],$ticket->id))
                                    <tr data-record-type="ticket" data-record-id="{{$ticket->id}}" data-open-url="{{$openUrl}}" data-assign-url="{{$openUrl}}" data-complete-url="{{$openUrl}}">
                                        <td><input type="checkbox" class="form-check-input bulk-check-item"></td>
                                        <td>
                                            <div class="fw-bold">{{$ticket->uidTicket()}}</div>
                                            <div class="text-muted fs-8">{{$ticket->oggetto}}</div>
                                        </td>
                                        <td>{!! $ticket->labelStatoTicket() !!}</td>
                                        <td>{{$etaGiorni}} gg</td>
                                        <td><span class="badge {{$prioritaClass}}">{{$priorita}}</span></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-light-primary" href="{{$openUrl}}">Apri</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">In attesa documenti</h3>
                    <div class="card-toolbar d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="open" data-bulk-target="#queue-docs">Apri</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="assign" data-bulk-target="#queue-docs">Assegna</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="complete" data-bulk-target="#queue-docs">Completa</button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if($inAttesaDocumenti->isEmpty())
                        <div class="text-muted">Nessuna pratica in attesa documenti nel filtro corrente.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle" id="queue-docs">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th style="width:30px"><input type="checkbox" class="form-check-input bulk-check-all"></th>
                                    <th>Tipo</th>
                                    <th>Cliente</th>
                                    <th>Età</th>
                                    <th>Stato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($inAttesaDocumenti as $item)
                                    <tr data-record-type="{{$item['record_type']}}" data-record-id="{{$item['id']}}" data-open-url="{{$item['open_url']}}" data-assign-url="{{$item['assign_url']}}" data-complete-url="{{$item['complete_url']}}">
                                        <td><input type="checkbox" class="form-check-input bulk-check-item"></td>
                                        <td><span class="badge badge-light-info">{{$item['tipo']}}</span></td>
                                        <td class="fw-bold">{{$item['cliente']}}</td>
                                        <td>{{$item['eta_giorni']}} gg</td>
                                        <td>
                                            @if($item['eta_giorni'] >= 3)
                                                <span class="badge badge-light-warning">Ferma da &gt; 3 giorni</span>
                                            @else
                                                <span class="badge badge-light-primary">In lavorazione</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-light-primary" href="{{$item['open_url']}}">Apri</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">Scadenze oggi</h3>
                    <div class="card-toolbar d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="open" data-bulk-target="#queue-deadline">Apri</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="assign" data-bulk-target="#queue-deadline">Assegna</button>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="complete" data-bulk-target="#queue-deadline">Completa</button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if($scadenzeOggi->isEmpty())
                        <div class="text-muted">Nessuna scadenza per oggi.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle" id="queue-deadline">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th style="width:30px"><input type="checkbox" class="form-check-input bulk-check-all"></th>
                                    <th>Tipo</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($scadenzeOggi as $item)
                                    <tr data-record-type="{{$item['tipo']}}" data-record-id="{{$item['id']}}" data-open-url="{{$item['apri_url']}}" data-assign-url="{{$item['assegna_url']}}" data-complete-url="{{$item['completa_url']}}">
                                        <td><input type="checkbox" class="form-check-input bulk-check-item"></td>
                                        <td><span class="badge badge-light-secondary text-uppercase">{{$item['tipo']}}</span></td>
                                        <td class="fw-bold">{{$item['cliente']}}</td>
                                        <td>{{$item['data']?->format('d/m/Y')}}</td>
                                        <td class="text-end text-nowrap">
                                            <a class="btn btn-sm btn-light-primary" href="{{$item['apri_url']}}">Apri</a>
                                            <a class="btn btn-sm btn-light-primary" href="{{$item['assegna_url']}}">Assegna</a>
                                            <a class="btn btn-sm btn-light-success" href="{{$item['completa_url']}}">Completa</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-6">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Monitor operativo</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Trend 7 giorni</span>
                        <span class="fw-bold">{{number_format($monitorOperativo['trend_7d'])}}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Trend 30 giorni</span>
                        <span class="fw-bold">{{number_format($monitorOperativo['trend_30d'])}}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Pratiche in attenzione</span>
                        <span class="fw-bold {{ $monitorOperativo['pratiche_attenzione'] >= $monitorOperativo['soglia_rossa'] ? 'text-danger' : ($monitorOperativo['pratiche_attenzione'] >= $monitorOperativo['soglia_gialla'] ? 'text-warning' : 'text-success') }}">{{number_format($monitorOperativo['pratiche_attenzione'])}}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Ferme da &gt; X giorni</span>
                        <span class="fw-bold {{ $monitorOperativo['ferme_oltre_x_giorni'] >= $monitorOperativo['soglia_rossa'] ? 'text-danger' : ($monitorOperativo['ferme_oltre_x_giorni'] >= $monitorOperativo['soglia_gialla'] ? 'text-warning' : 'text-success') }}">{{number_format($monitorOperativo['ferme_oltre_x_giorni'])}}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Tempo medio risposta</span>
                        <span class="fw-bold">{{$monitorOperativo['tempo_medio_risposta_ore']}} ore</span>
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Timeline attività personale</h3>
                </div>
                <div class="card-body pt-0">
                    @if($timelineAttivita->isEmpty())
                        <div class="text-muted">Nessuna attività registrata.</div>
                    @else
                        <div class="timeline-label">
                            @foreach($timelineAttivita as $item)
                                <div class="timeline-item mb-5">
                                    <div class="timeline-label fw-bold text-gray-800 fs-8">{{$item['quando']?->format('d/m H:i')}}</div>
                                    <div class="timeline-badge">
                                        <i class="fa fa-genderless text-primary fs-1"></i>
                                    </div>
                                    <div class="timeline-content ps-3">
                                        <div class="fw-bold">{{$item['tipo']}} - {{$item['descrizione']}}</div>
                                        <div class="text-muted fs-8">Prossima azione: {{$item['prossima_azione']}}</div>
                                        <a href="{{$item['url']}}" class="fs-8 text-primary">Apri</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Accesso rapido moduli</h3>
                </div>
                <div class="card-body pt-0 d-flex flex-wrap gap-2">
                    @can('servizio_ticket')
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}" class="btn btn-sm btn-light">Ticket</a>
                    @endcan
                    @can('servizio_visure')
                        <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}" class="btn btn-sm btn-light">Visure</a>
                    @endcan
                    @can('servizio_caf_patronato')
                        <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}" class="btn btn-sm btn-light">CAF/Patronato</a>
                    @endcan
                    @can('servizio_contratti_telefonia')
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}" class="btn btn-sm btn-light">Telefonia</a>
                    @endcan
                    @can('servizio_contratti_energia')
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}" class="btn btn-sm btn-light">Energia</a>
                    @endcan
                    <a href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'index'])}}" class="btn btn-sm btn-light">Portafoglio</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customCss')
@endpush
@push('customScript')
    <script>
        const bulkActionUrl = '{{ action([\App\Http\Controllers\Backend\DashboardController::class, 'bulkAction']) }}';

        $(function () {
            $(document).on('change', '.bulk-check-all', function () {
                const table = $(this).closest('table');
                table.find('.bulk-check-item').prop('checked', $(this).is(':checked'));
            });

            $(document).on('click', '[data-bulk-action]', function () {
                const actionType = $(this).data('bulk-action');
                const target = $(this).data('bulk-target');
                const rows = $(target).find('.bulk-check-item:checked').closest('tr');

                if (!rows.length) {
                    Swal.fire('Seleziona almeno un elemento', 'Per usare l\'azione rapida seleziona almeno una riga.', 'warning');
                    return;
                }

                const items = [];
                rows.each(function () {
                    const row = $(this);
                    const type = row.data('record-type');
                    const id = row.data('record-id');
                    if (type && id) {
                        items.push({type: type, id: parseInt(id)});
                    }
                });

                if (!items.length) {
                    Swal.fire('Azione non disponibile', 'Nessun elemento valido selezionato.', 'info');
                    return;
                }

                $.ajax({
                    url: bulkActionUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        azione: actionType,
                        items: items
                    },
                    success: function (response) {
                        if (!response || !response.success) {
                            Swal.fire('Errore', 'Non è stato possibile completare l\'azione.', 'error');
                            return;
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }

                        Swal.fire('Operazione completata', response.message || 'Azione eseguita.', 'success').then(function () {
                            window.location.reload();
                        });
                    },
                    error: function () {
                        Swal.fire('Errore', 'Si è verificato un errore durante l\'azione bulk.', 'error');
                    }
                });
            });
        });
    </script>
@endpush

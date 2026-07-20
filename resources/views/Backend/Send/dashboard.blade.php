@extends('Backend._layout._main')

@php
    $isAgentView = (bool) ($stats['is_agent_view'] ?? false);
    $isSupervisorView = (bool) ($stats['is_supervisor_view'] ?? false);
@endphp

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        @can('create', \App\Models\SendRequest::class)
            <a href="{{ action([$controller, 'create']) }}" class="btn btn-sm btn-primary">Nuova richiesta</a>
        @endcan
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">
            @if($isAgentView) Mie richieste @elseif($isSupervisorView) Tutte le richieste @else Elenco richieste @endif
        </a>
        @if($isSupervisorView)
            <a href="{{ action([$controller, 'queue']) }}" class="btn btn-sm btn-primary">La mia coda</a>
        @endif
        @if(! $isSupervisorView)
            <a href="{{ action([$controller, 'integrations']) }}" class="btn btn-sm btn-light-warning">Da integrare</a>
        @endif
        @can('send.requests.process')
            @if(! $isSupervisorView)
                <a href="{{ action([$controller, 'queue']) }}" class="btn btn-sm btn-light-info">Coda supervisore</a>
            @endif
        @endcan
        @can('manageSettings', \App\Models\SendRequest::class)
            <a href="{{ action([$controller, 'settings']) }}" class="btn btn-sm btn-light">Impostazioni</a>
        @endcan
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($isAgentView)
        <div class="alert alert-light-primary d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <div class="fw-bold">Sportello SEND — le tue pratiche</div>
                <div class="fs-7 text-muted">Monitora bozze, integrazioni e consegne al cittadino.</div>
            </div>
            @can('create', \App\Models\SendRequest::class)
                <a href="{{ action([$controller, 'create']) }}" class="btn btn-sm btn-primary">Nuova richiesta</a>
            @endcan
        </div>
    @elseif($isSupervisorView)
        <div class="alert alert-light-info d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <div class="fw-bold">Coda supervisore SEND</div>
                <div class="fs-7 text-muted">Pratiche assegnate a te: prendi in carico, lavora e completa.</div>
            </div>
            <a href="{{ action([$controller, 'queue']) }}" class="btn btn-sm btn-primary">Apri coda operativa</a>
        </div>
    @endif

    <div class="row g-5 mb-6">
        @foreach($stats['kpis'] as $kpi)
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0" style="background:#f8fafc;">
                    <div class="card-body py-5">
                        <div class="text-muted fw-semibold fs-7 text-uppercase">{{ $kpi['label'] }}</div>
                        <div class="fs-2hx fw-bold mt-2
                            @if(in_array($kpi['key'], ['urgent', 'integration', 'deliver', 'take', 'pending'], true) && $kpi['value'] > 0) text-danger @endif">
                            {{ $kpi['value'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title fw-bold">
                        @if($isAgentView) Le mie pipeline
                        @elseif($isSupervisorView) Pipeline della mia coda
                        @else Pipeline operative
                        @endif
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-4">
                        @foreach($stats['pipeline'] as $col)
                            @php
                                $pipelineHref = $isSupervisorView
                                    ? action([$controller, 'queue'], ($col['key'] ?? '') === 'assigned'
                                        ? []
                                        : ['status' => $col['status']])
                                    : action([$controller, 'index'], ['status' => $col['status']]);
                            @endphp
                            <div class="col-6 col-md-4">
                                <a href="{{ $pipelineHref }}"
                                   class="d-block border rounded p-4 h-100 text-decoration-none text-gray-800 bg-hover-light">
                                    <div class="text-muted fs-8 fw-semibold text-uppercase">{{ $col['label'] }}</div>
                                    <div class="fs-2 fw-bolder mt-1">{{ $col['value'] }}</div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title fw-bold">
                        @if($isAgentView) Tariffe e plafond
                        @elseif($isSupervisorView) Riepilogo operativo
                        @else Tariffe sportello
                        @endif
                    </h3>
                </div>
                <div class="card-body pt-0">
                    @if($isSupervisorView)
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">Senza supervisore</span>
                            <span class="fw-bold @if(($stats['supervisor']['pending_assignment'] ?? 0) > 0) text-danger @endif">{{ $stats['supervisor']['pending_assignment'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">Da prendere in carico</span>
                            <span class="fw-bold text-danger fs-4">{{ $stats['supervisor']['to_take_charge'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">In lavorazione</span>
                            <span class="fw-bold">{{ $stats['supervisor']['in_work'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">Urgenti</span>
                            <span class="fw-bold">{{ $stats['supervisor']['urgent'] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">Completate oggi</span>
                            <span class="fw-bold">{{ $stats['supervisor']['completed_today'] ?? 0 }}</span>
                        </div>
                        <div class="form-text mt-3">Lavora le pratiche fuori Gestiio, poi carica l’allegato SEND e completa.</div>
                    @else
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">Importo cliente</span>
                            <span class="fw-bold text-primary fs-4">{!! importo($stats['pricing']['prezzo_cliente'], true) !!}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="text-muted">Addebito plafond</span>
                            <span class="fw-bold">{!! importo($stats['pricing']['prezzo_agente'], true) !!}</span>
                        </div>
                        @if($isAgentView && $stats['pricing']['plafond_servizi'] !== null)
                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                                <span class="text-muted">Plafond servizi</span>
                                <span class="fw-bold @if($stats['pricing']['plafond_servizi'] < $stats['pricing']['prezzo_agente']) text-danger @endif">
                                    {!! importo($stats['pricing']['plafond_servizi'], true) !!}
                                </span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">{{ $isAgentView ? 'Completate oggi (mie)' : 'Completate oggi' }}</span>
                            <span class="fw-bold">{{ $stats['pricing']['completed_today'] }}</span>
                        </div>
                        <div class="form-text mt-3">
                            Scalo plafond alla creazione della pratica.
                            Totale {{ $isAgentView ? 'mie ' : '' }}pratiche: <strong>{{ $stats['total'] }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title fw-bold">
                @if($isAgentView) Ultime mie richieste
                @elseif($isSupervisorView) Pratiche da lavorare
                @else Ultime richieste
                @endif
            </h3>
            <div class="card-toolbar">
                @if($isSupervisorView)
                    <a href="{{ action([$controller, 'queue']) }}" class="btn btn-sm btn-light">Vedi coda</a>
                @else
                    <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Vedi tutte</a>
                @endif
            </div>
        </div>
        <div class="card-body pt-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-3 gy-3">
                <thead>
                <tr class="fw-bold text-muted">
                    <th>Codice</th>
                    <th>Stato</th>
                    <th>Priorità</th>
                    <th>Soggetto</th>
                    <th>
                        @if($isAgentView) Supervisore
                        @elseif($isSupervisorView) Operatore
                        @else Operatore
                        @endif
                    </th>
                    <th>Creata</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($stats['recent'] as $row)
                    @php
                        $dest = $row->subjects->firstWhere('subject_role', 'destinatario')
                            ?? $row->subjects->firstWhere('subject_role', 'impresa')
                            ?? $row->subjects->first();
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $row->request_number }}</td>
                        <td><span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span></td>
                        <td><span class="badge {{ $row->priority->badgeClass() }}">{{ $row->priority->label() }}</span></td>
                        <td>{{ $dest?->displayName() ?: '—' }}</td>
                        <td>
                            @if($isAgentView)
                                {{ $row->supervisor?->nominativo() ?: '—' }}
                            @else
                                {{ $row->creator?->nominativo() ?: '—' }}
                            @endif
                        </td>
                        <td>{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end text-nowrap">
                            @can('claim', $row)
                                <form method="post" action="{{ action([$controller, 'claim'], $row) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-warning">Assegna a me</button>
                                </form>
                            @endcan
                            @can('takeCharge', $row)
                                <form method="post" action="{{ action([$controller, 'takeCharge'], $row) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Prendi in carico</button>
                                </form>
                            @endcan
                            <a class="btn btn-sm btn-light" href="{{ action([$controller, 'show'], $row) }}">Apri</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-10">
                            @if($isAgentView)
                                Nessuna pratica ancora. Crea la prima richiesta.
                            @elseif($isSupervisorView)
                                Nessuna pratica assegnata da lavorare.
                            @else
                                Nessuna richiesta ancora.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

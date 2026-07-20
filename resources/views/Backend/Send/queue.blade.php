@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap gap-2 py-1">
        <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-sm btn-light">Dashboard</a>
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Tutte</a>
        <a href="{{ action([$controller, 'queue'], ['status' => 'assigned']) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === 'assigned' ? 'btn-primary' : 'btn-light' }}">Da prendere</a>
        <a href="{{ action([$controller, 'queue'], ['status' => 'resubmitted']) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === 'resubmitted' ? 'btn-primary' : 'btn-light' }}">Reinviati</a>
        <a href="{{ action([$controller, 'queue'], ['status' => 'taken_in_charge']) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === 'taken_in_charge' ? 'btn-primary' : 'btn-light' }}">In carico</a>
        <a href="{{ action([$controller, 'queue'], ['status' => 'processing']) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === 'processing' ? 'btn-primary' : 'btn-light' }}">In lavorazione</a>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(!empty($queueStats))
        <div class="row g-5 mb-6">
            <div class="col-6 col-md-3">
                <div class="card border-0 h-100" style="background:#f8fafc;">
                    <div class="card-body py-5">
                        <div class="text-muted fw-semibold fs-8 text-uppercase">Da prendere</div>
                        <div class="fs-2hx fw-bold mt-1 @if(($queueStats['to_take'] ?? 0) > 0) text-danger @endif">{{ $queueStats['to_take'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 h-100" style="background:#f8fafc;">
                    <div class="card-body py-5">
                        <div class="text-muted fw-semibold fs-8 text-uppercase">In carico</div>
                        <div class="fs-2hx fw-bold mt-1">{{ $queueStats['taken'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 h-100" style="background:#f8fafc;">
                    <div class="card-body py-5">
                        <div class="text-muted fw-semibold fs-8 text-uppercase">In lavorazione</div>
                        <div class="fs-2hx fw-bold mt-1">{{ $queueStats['processing'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 h-100" style="background:#f8fafc;">
                    <div class="card-body py-5">
                        <div class="text-muted fw-semibold fs-8 text-uppercase">Urgenti</div>
                        <div class="fs-2hx fw-bold mt-1 @if(($queueStats['urgent'] ?? 0) > 0) text-danger @endif">{{ $queueStats['urgent'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title fw-bold">Coda supervisore SEND</h3>
        </div>
        <div class="card-body pt-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-3 gy-3">
                <thead>
                <tr class="fw-bold text-muted">
                    <th>Codice</th>
                    <th>Stato</th>
                    <th>Priorità</th>
                    <th>Soggetto</th>
                    <th>Operatore</th>
                    <th>Avviso</th>
                    <th>Inviata</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $row)
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
                        <td>{{ $row->creator?->nominativo() ?: '—' }}</td>
                        <td>{{ $row->send_notice_identifier ?: $row->iun ?: '—' }}</td>
                        <td>{{ optional($row->submitted_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ action([$controller, 'show'], $row) }}">Apri</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Nessuna pratica in questa coda.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $records->links() }}
        </div>
    </div>
@endsection

@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-sm btn-light">Dashboard</a>
        <a href="{{ action([$controller, 'exportCsv'], request()->query()) }}" class="btn btn-sm btn-light-primary">Esporta CSV</a>
    </div>
@endsection

@section('content')
    <div class="card mb-5">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Dal</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Al</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stato</label>
                    <select name="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Aggiorna</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-5 mb-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Pratiche nel periodo</div>
                    <div class="fs-2hx fw-bold">{{ $totals['count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Totale importo cliente</div>
                    <div class="fs-2hx fw-bold text-primary">{!! importo($totals['prezzo_cliente'], true) !!}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Totale addebito plafond</div>
                    <div class="fs-2hx fw-bold">{!! importo($totals['prezzo_agente'], true) !!}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header"><h3 class="card-title">Conteggi per stato</h3></div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($statuses as $st)
                    @php $n = (int) ($byStatus[$st->value] ?? 0); @endphp
                    @if($n > 0)
                        <div class="col-md-3">
                            <div class="border rounded p-3 d-flex justify-content-between">
                                <span class="badge {{ $st->badgeClass() }}">{{ $st->label() }}</span>
                                <strong>{{ $n }}</strong>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Dettaglio (max 500)</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-row-bordered align-middle gs-3">
                <thead>
                <tr class="fw-bold text-muted">
                    <th>Codice</th>
                    <th>Stato</th>
                    <th>Cliente</th>
                    <th>Plafond</th>
                    <th>Operatore</th>
                    <th>Creata</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->request_number }}</td>
                        <td><span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span></td>
                        <td>{!! importo($row->prezzo_cliente, true) !!}</td>
                        <td>{!! importo($row->prezzo_agente, true) !!}</td>
                        <td>{{ $row->creator?->nominativo() }}</td>
                        <td>{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-light" href="{{ action([$controller, 'show'], $row) }}">Apri</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-10">Nessun dato nel periodo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

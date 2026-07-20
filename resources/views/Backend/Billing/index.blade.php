@extends('Backend._layout._main')

@section('toolbar')
    <a href="{{ action([$controller, 'invoiceshelfIndex']) }}" class="btn btn-sm btn-light-success">
        XML FatturaPA
    </a>
    <form method="get">
        <select name="source" class="form-select form-select-sm form-select-solid w-175px" onchange="this.form.submit()">
            <option value="">Tutte le fonti</option>
            <option value="caf_monthly" @selected(($filtri['source'] ?? '') === 'caf_monthly')>CAF/Patronato</option>
            <option value="send_monthly" @selected(($filtri['source'] ?? '') === 'send_monthly')>SEND</option>
            <option value="agent_proforma" @selected(($filtri['source'] ?? '') === 'agent_proforma')>Agenti</option>
        </select>
        <select name="status" class="form-select form-select-sm form-select-solid w-140px" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            @foreach(['bozza','emessa','inviata','pagata'] as $st)
                <option value="{{ $st }}" @selected(($filtri['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('content')
    @include('Backend._components.alertMessage')
    @if(!($invoiceshelfEnabled ?? false))
        <div class="alert alert-light border">InvoiceShelf offline — documenti generabili in modalità locale.</div>
    @endif
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder">
                        <th>#</th>
                        <th>Fonte</th>
                        <th>Periodo</th>
                        <th>Stato</th>
                        <th class="text-end">Totale</th>
                        <th>IS</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->labelSource() }}</td>
                            <td>{{ $row->periodo }}</td>
                            <td><span class="badge {{ $row->statusBadgeClass() }}">{{ ucfirst($row->status) }}</span></td>
                            <td class="text-end">{!! importo($row->totale, true) !!}</td>
                            <td>
                                @if($row->invoiceshelf_id)
                                    {{ $row->invoiceshelf_type }} #{{ $row->invoiceshelf_id }}
                                @else
                                    <span class="text-muted">locale</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-light" href="{{ action([$controller, 'show'], $row->id) }}">Apri</a>
                                <a class="btn btn-sm btn-light-primary" href="{{ action([$controller, 'pdf'], $row->id) }}" target="_blank" rel="noopener">PDF</a>
                                @if($row->invoiceshelf_type === 'invoice' && $row->invoiceshelf_id)
                                    <a class="btn btn-sm btn-light-success" href="{{ action([$controller, 'exportXml'], $row->id) }}">XML</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Nessun documento.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="text-center">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@extends('Backend._layout._main')

@section('toolbar')
    <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Elenco</a>
    <a href="{{ action([$controller, 'pdf'], $record->id) }}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">PDF</a>
    @if($record->invoiceshelf_type === 'invoice' && $record->invoiceshelf_id)
        <a href="{{ action([$controller, 'exportXml'], $record->id) }}" class="btn btn-sm btn-light-success">XML FatturaPA</a>
    @endif
    @if($record->status === 'bozza')
        <form method="post" action="{{ action([$controller, 'emetti'], $record->id) }}">
            @csrf
            <button class="btn btn-sm btn-primary" type="submit">Emetti</button>
        </form>
    @endif
    @if(in_array($record->status, ['bozza','emessa','inviata'], true) && !$record->isPaid())
        <form method="post" action="{{ action([$controller, 'segnaPagata'], $record->id) }}">
            @csrf
            <button class="btn btn-sm btn-success" type="submit">Segna pagata</button>
        </form>
    @endif
    @if($record->invoiceshelf_type === 'estimate' && $record->invoiceshelf_id)
        <form method="post" action="{{ action([$controller, 'convertToInvoice'], $record->id) }}">
            @csrf
            <button class="btn btn-sm btn-light-primary" type="submit">Converti in fattura IS</button>
        </form>
    @endif
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <div class="card mb-5">
        <div class="card-header">
            <h3 class="card-title">{{ $record->labelSource() }} — {{ $record->periodo }}</h3>
            <div class="card-toolbar">
                <span class="badge {{ $record->statusBadgeClass() }} fs-7">{{ ucfirst($record->status) }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-5 mb-5">
                <div class="col-md-3">
                    <div class="text-muted">Totale</div>
                    <div class="fs-2 fw-bold">{!! importo($record->totale, true) !!}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">InvoiceShelf</div>
                    <div>
                        @if($record->invoiceshelf_id)
                            {{ $record->invoiceshelf_type }} #{{ $record->invoiceshelf_id }}
                        @else
                            Modalità offline
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Pratiche</div>
                    <div>{{ $record->meta['count'] ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Generata</div>
                    <div>{{ $record->meta['generated_at'] ?? $record->created_at }}</div>
                </div>
            </div>

            <h4 class="mb-3">Righe</h4>
            <div class="table-responsive">
                <table class="table table-row-bordered">
                    <thead>
                    <tr class="fw-bolder">
                        <th>Descrizione</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Prezzo</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach(($record->meta['items'] ?? []) as $item)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $item['name'] ?? '' }}</div>
                                <div class="text-muted fs-8">{{ $item['description'] ?? '' }}</div>
                            </td>
                            <td class="text-end">{{ $item['quantity'] ?? 1 }}</td>
                            <td class="text-end">{!! importo($item['price'] ?? 0, true) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if(!empty($remote['error']))
                <div class="alert alert-warning mt-5">Sync InvoiceShelf: {{ $remote['error'] }}</div>
            @endif
        </div>
    </div>
@endsection

@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-nowrap align-items-center gap-2">
        <a href="{{ $backAction }}" class="btn btn-sm btn-light flex-shrink-0">Torna all'hub</a>
        @if(($preview['ok'] ?? false))
            <form method="post" action="{{ $generateAction }}" class="d-inline flex-shrink-0">
                @csrf
                <input type="hidden" name="anno" value="{{ $anno }}">
                <input type="hidden" name="mese" value="{{ $mese }}">
                <button class="btn btn-sm btn-primary" type="submit">Genera proforma</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <div class="card mb-5">
        <div class="card-header">
            <h3 class="card-title">Anteprima {{ $preview['label'] ?? '' }}</h3>
        </div>
        <div class="card-body">
            <div class="mb-5">
                <strong>Totale fornitore:</strong> {!! importo($preview['totale'] ?? 0, true) !!}
                · <strong>Pratiche:</strong> {{ $preview['count'] ?? 0 }}
                · <strong>Escluse:</strong> {{ $preview['excluded_count'] ?? 0 }}
            </div>

            @if(!($preview['ok'] ?? false))
                <div class="alert alert-warning">{{ $preview['error'] ?? 'Nessun dato' }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-row-bordered">
                    <thead>
                    <tr class="fw-bolder">
                        <th>ID</th>
                        <th>Riferimento</th>
                        <th class="text-end">Importo fornitore</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($preview['rows'] ?? [] as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>
                                @if($source === \App\Models\BillingDocument::SOURCE_SEND_MONTHLY)
                                    {{ $row->request_number }}
                                @else
                                    {{ $row->nome ?? '—' }}
                                @endif
                            </td>
                            <td class="text-end">{!! importo($row->importo_fornitore ?? 0, true) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

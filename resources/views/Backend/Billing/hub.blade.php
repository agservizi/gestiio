@extends('Backend._layout._main')

@section('toolbar')
    {{-- Pulsanti prima dei filtri (come XML su /fatturazione), così restano sempre visibili --}}
    @if($existing)
        <a class="btn btn-sm btn-primary"
           href="{{ action([\App\Http\Controllers\Backend\BillingDocumentController::class, 'show'], $existing->id) }}">
            Apri proforma
        </a>
        @if(!$existing->isPaid())
            <form method="post" action="{{ $generateAction }}"
                  onsubmit="return confirm('Rigenerare la proforma per questo periodo?');">
                @csrf
                <input type="hidden" name="anno" value="{{ $anno }}">
                <input type="hidden" name="mese" value="{{ $mese }}">
                <input type="hidden" name="force" value="1">
                <button class="btn btn-sm btn-warning" type="submit">Rigenera</button>
            </form>
        @endif
    @else
        <form method="post" action="{{ $generateAction }}">
            @csrf
            <input type="hidden" name="anno" value="{{ $anno }}">
            <input type="hidden" name="mese" value="{{ $mese }}">
            <button class="btn btn-sm btn-primary" type="submit" @disabled(!($preview['ok'] ?? false))>Genera proforma</button>
        </form>
    @endif

    <a class="btn btn-sm btn-light-primary" href="{{ $previewAction }}?anno={{ $anno }}&mese={{ $mese }}">Anteprima</a>

    <form method="get">
        <select name="anno" class="form-select form-select-sm form-select-solid w-100px" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" @selected($anno == $y)>{{ $y }}</option>
            @endfor
        </select>
        <select name="mese" class="form-select form-select-sm form-select-solid w-120px" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($mese == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
            @endfor
        </select>
    </form>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    @if(!($invoiceshelfEnabled ?? false))
        <div class="alert alert-warning">
            InvoiceShelf non è attivo (<code>INVOICESHELF_ENABLED</code>). Puoi comunque generare proforma locali (offline); i PDF IS saranno disponibili dopo la configurazione.
        </div>
    @endif

    @if(!($preview['ok'] ?? false) && !empty($preview['error']))
        <div class="alert alert-light border">{{ $preview['error'] }}</div>
    @endif

    <div class="row g-5 mb-5">
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Periodo</div>
                    <div class="fs-2 fw-bold">{{ $preview['label'] ?? $periodo }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Pratiche eligible</div>
                    <div class="fs-2 fw-bold">{{ $preview['count'] ?? 0 }}</div>
                    <div class="text-muted fs-8">Escluse: {{ $preview['excluded_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Totale fornitore</div>
                    <div class="fs-2 fw-bold text-primary">{!! importo($preview['totale'] ?? 0, true) !!}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Storico</h3></div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-bordered">
                    <thead>
                    <tr class="fw-bolder text-gray-800">
                        <th>Periodo</th>
                        <th>Stato</th>
                        <th class="text-end">Totale</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>{{ $row->periodo }}</td>
                            <td><span class="badge {{ $row->statusBadgeClass() }}">{{ ucfirst($row->status) }}</span></td>
                            <td class="text-end">{!! importo($row->totale, true) !!}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\BillingDocumentController::class, 'show'], $row->id) }}">Apri</a>
                                <a class="btn btn-sm btn-light-primary" href="{{ action([\App\Http\Controllers\Backend\BillingDocumentController::class, 'pdf'], $row->id) }}" target="_blank" rel="noopener">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Nessuna proforma ancora.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

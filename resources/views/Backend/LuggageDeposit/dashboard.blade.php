@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <form method="get" action="{{ action([$controller, 'checkInPage']) }}" class="d-flex align-items-center gap-2">
            <input type="text" name="code" class="form-control form-control-sm form-control-solid w-200px" placeholder="Codice check-in">
            <button type="submit" class="btn btn-sm btn-light-primary">Check-in</button>
        </form>
        <a href="{{ action([$controller, 'pipeline']) }}" class="btn btn-sm btn-light">Pipeline</a>
        <a href="{{ action([$controller, 'checkOutPage']) }}" class="btn btn-sm btn-light-success">Check-out</a>
        <a href="{{ action([$controller, 'create']) }}" class="btn btn-sm btn-primary">Nuovo deposito</a>
        @can('viewReports', \App\Models\LuggageDeposit::class)
        <a href="{{ action([$controller, 'exportCsv']) }}" class="btn btn-sm btn-light">Export CSV</a>
        @endcan
    </div>
@endsection

@section('content')
    @php
        $kpis = collect($stats['kpis'])->keyBy('key');
        $capacity = $stats['capacity'];
        $pipeline = $stats['pipeline'];
        $maxTrend = max(1, collect($stats['revenue_trend'])->max('revenue'));
        $totalSources = max(1, array_sum($stats['source_split']));
    @endphp

    <div class="row g-5 mb-6">
        @foreach($stats['kpis'] as $kpi)
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0" style="background:#f8fafc;">
                    <div class="card-body">
                        <div class="text-muted fw-semibold fs-7">{{ $kpi['label'] }}</div>
                        <div class="fs-2hx fw-bold mt-2">{{ $kpi['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 mb-6">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fs-7">Occupazione oggi</div>
                    <div class="fs-2 fw-bolder">{{ $capacity['utilization'] }}%</div>
                    <div class="text-muted fs-8 mt-2">{{ $capacity['booked'] }} prenotate/in custodia · {{ $capacity['available'] }} posti liberi</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="{{ action([$controller, 'checkInPage']) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-muted fs-7">Check-in in attesa</div>
                    <div class="fs-2 fw-bolder text-warning">{{ $pipeline['prenotati'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ action([$controller, 'checkOutPage']) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-muted fs-7">In custodia</div>
                    <div class="fs-2 fw-bolder text-success">{{ $pipeline['attivi'] }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Incassi · ultimi 7 giorni</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-end gap-3" style="min-height:180px">
                        @foreach($stats['revenue_trend'] as $point)
                            <div class="flex-grow-1 text-center">
                                <div class="bg-primary rounded-top mx-auto" style="width:70%;height:{{ max(8, ($point['revenue'] / $maxTrend) * 120) }}px" title="€ {{ number_format($point['revenue'], 2, ',', '.') }}"></div>
                                <div class="fs-8 fw-bold mt-2">{{ $point['label'] }}</div>
                                <div class="text-muted fs-9">{{ $point['completed'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Canali prenotazione</h3>
                </div>
                <div class="card-body">
                    @foreach($stats['source_split'] as $source => $count)
                        @php $pct = round(($count / $totalSources) * 100); @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">{{ $source === 'PORTALE' ? 'Portale web' : 'Sportello' }}</span>
                                <span>{{ $count }} ({{ $pct }}%)</span>
                            </div>
                            <div class="progress h-6px">
                                <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ url('/deposito-bagagli') }}" target="_blank" class="btn btn-sm btn-light-primary">Pagina pubblica</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Stati operativi</h3>
                    <div class="card-toolbar">
                        <a href="{{ action([$controller, 'pipeline']) }}" class="btn btn-sm btn-light">Pipeline</a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @php $totalStatus = max(1, collect($stats['status_breakdown'])->sum('count')); @endphp
                    @foreach($stats['status_breakdown'] as $status => $item)
                        @php $pct = round(($item['count'] / $totalStatus) * 100); @endphp
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <span class="badge {{ $item['badgeClass'] }}">{{ $item['label'] }}</span>
                            <strong>{{ $item['count'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Attività recente</h3>
                    <div class="card-toolbar">
                        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Elenco completo</a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @forelse($stats['recent'] as $item)
                        <a href="{{ action([$controller, 'show'], $item->id) }}" class="d-flex justify-content-between align-items-center py-3 border-bottom text-gray-800 text-hover-primary">
                            <div>
                                <strong>{{ $item->code }}</strong>
                                <div class="text-muted fs-7">{{ $item->customer_name }}</div>
                            </div>
                            <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                        </a>
                    @empty
                        <p class="text-muted mb-0 py-5">Nessun deposito recente.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

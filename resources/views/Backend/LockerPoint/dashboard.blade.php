@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <form method="get" action="{{ url('/backend/locker-point/accetta') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="code" class="form-control form-control-sm form-control-solid w-200px" placeholder="Codice pacco">
            <button type="submit" class="btn btn-sm btn-light-primary">Accettazione</button>
        </form>
        <a href="{{ url('/backend/locker-point/pipeline') }}" class="btn btn-sm btn-light">Pipeline</a>
        <a href="{{ url('/backend/locker-point/create') }}" class="btn btn-sm btn-primary">Nuovo pacco</a>
        @can('manageSettings', \App\Models\LockerPackage::class)
            <a href="{{ url('/backend/locker-point/settings') }}" class="btn btn-sm btn-light">Impostazioni</a>
        @endcan
    </div>
@endsection

@section('content')
    @php
        $capacity = $stats['capacity'];
        $pipeline = $stats['pipeline'];
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
                    <div class="text-muted fs-8 mt-2">{{ $capacity['booked'] }} pacchi prenotati/in giacenza · {{ $capacity['available'] }} posti liberi</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="{{ url('/backend/locker-point') }}?view=prenotati" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-muted fs-7">Prenotati in attesa</div>
                    <div class="fs-2 fw-bolder text-primary">{{ $pipeline['prenotati'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ url('/backend/locker-point') }}?view=giacenza" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <div class="text-muted fs-7">In giacenza</div>
                    <div class="fs-2 fw-bolder text-warning">{{ $pipeline['giacenza'] }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Stati operativi</h3>
                    <div class="card-toolbar">
                        <a href="{{ url('/backend/locker-point/pipeline') }}" class="btn btn-sm btn-light">Pipeline</a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="badge badge-light-primary">Prenotato</span>
                        <strong>{{ $pipeline['prenotati'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="badge badge-light-warning">In giacenza</span>
                        <strong>{{ $pipeline['giacenza'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <span class="badge badge-light-success">Consegnati oggi</span>
                        <strong>{{ $pipeline['consegnati_oggi'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Attività recente</h3>
                    <div class="card-toolbar">
                        <a href="{{ url('/backend/locker-point') }}" class="btn btn-sm btn-light">Elenco completo</a>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @forelse($stats['recent'] as $item)
                        <a href="{{ url('/backend/locker-point/'.$item->id) }}" class="d-flex justify-content-between align-items-center py-3 border-bottom text-gray-800 text-hover-primary">
                            <div>
                                <strong>{{ $item->code }}</strong>
                                <div class="text-muted fs-7">{{ $item->recipient_name }}</div>
                            </div>
                            <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                        </a>
                    @empty
                        <p class="text-muted mb-0 py-5">Nessun pacco recente.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

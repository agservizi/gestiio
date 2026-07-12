@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller,'exportCsv'], request()->only(['from','to'])) }}" class="btn btn-sm btn-light-primary">Export CSV</a>
    </div>
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Da</label>
                    <input type="date" name="from" class="form-control form-control-solid" value="{{ $from }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">A</label>
                    <input type="date" name="to" class="form-control form-control-solid" value="{{ $to }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Filtra</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-5">
        @foreach($stats['kpis'] as $kpi)
            <div class="col-md-4">
                <div class="card h-100 border-0" style="background:#f8fafc;">
                    <div class="card-body">
                        <div class="text-muted fs-7">{{ $kpi['label'] }}</div>
                        <div class="fs-2hx fw-bold mt-2">{{ $kpi['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

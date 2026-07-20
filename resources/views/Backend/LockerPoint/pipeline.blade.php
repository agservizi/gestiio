@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ url('/backend/locker-point/accetta') }}" class="btn btn-sm btn-light-warning">Accettazione</a>
        <a href="{{ url('/backend/locker-point/create') }}" class="btn btn-sm btn-primary">Nuovo pacco</a>
        <a href="{{ url('/backend/locker-point') }}" class="btn btn-sm btn-light">Elenco</a>
    </div>
@endsection

@section('content')
    <div class="row g-5">
        @foreach(['prenotati' => 'Prenotati', 'giacenza' => 'In giacenza', 'consegnati' => 'Consegnati'] as $key => $title)
            <div class="col-lg-4">
                <div class="card card-flush h-100">
                    <div class="card-header">
                        <h3 class="card-title">{{ $title }}</h3>
                        <span class="badge badge-light-primary">{{ count($columns[$key]) }}</span>
                    </div>
                    <div class="card-body pt-0">
                        @forelse($columns[$key] as $item)
                            <div class="border rounded p-4 mb-3">
                                <div class="fw-bold">{{ $item->code }}</div>
                                <div class="text-muted fs-7">{{ $item->recipient_name }}@if($item->carrier) · {{ $item->carrier }}@endif</div>
                                <div class="text-muted fs-8">{{ $item->expected_pickup_date?->format('d/m/Y') }}</div>
                                <div class="mt-2">
                                    <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                                </div>
                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-light" href="{{ action([$controller, 'show'], $item->id) }}">Apri</a>
                                    @if($key === 'prenotati')
                                        <a class="btn btn-sm btn-warning" href="{{ action([$controller, 'intake'], $item->id) }}">Accettazione</a>
                                        @if($item->status->value === 'PRENOTATO')
                                            <form method="post" action="{{ action([$controller, 'action'], $item->id) }}">@csrf
                                                <input type="hidden" name="action" value="no-show">
                                                <button type="button" class="btn btn-sm btn-light-danger" onclick="return gestiioAsk(this, 'Segnare come no-show?', true)">No-show</button>
                                            </form>
                                        @endif
                                    @elseif($key === 'giacenza')
                                        <a class="btn btn-sm btn-success" href="{{ action([$controller, 'show'], $item->id) }}">Consegna</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Nessun elemento.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

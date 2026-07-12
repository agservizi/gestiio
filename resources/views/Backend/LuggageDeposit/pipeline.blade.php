@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller,'checkInPage']) }}" class="btn btn-sm btn-light-warning">Check-in</a>
        <a href="{{ action([$controller,'create']) }}" class="btn btn-sm btn-primary">Nuovo deposito</a>
        <a href="{{ action([$controller,'index']) }}" class="btn btn-sm btn-light">Elenco</a>
    </div>
@endsection

@section('content')
    <div class="row g-5">
        @foreach(['prenotati'=>'Prenotati','attivi'=>'In custodia','completati'=>'Completati'] as $key=>$title)
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
                                <div class="text-muted fs-7">{{ $item->customer_name }} · {{ $item->bag_count }} borse</div>
                                <div class="text-muted fs-8">{{ $item->booking_date?->format('d/m/Y') }}</div>
                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-light" href="{{ action([$controller,'show'], $item->id) }}">Apri</a>
                                    @if($key === 'prenotati')
                                        <form method="post" action="{{ action([$controller,'action'], $item->id) }}">@csrf
                                            <input type="hidden" name="action" value="check-in">
                                            <button class="btn btn-sm btn-warning">Check-in</button>
                                        </form>
                                        @if($item->status->value === 'PRENOTATO')
                                            <form method="post" action="{{ action([$controller,'action'], $item->id) }}">@csrf
                                                <input type="hidden" name="action" value="no-show">
                                                <button class="btn btn-sm btn-light-danger" onclick="return confirm('No-show?')">No-show</button>
                                            </form>
                                        @endif
                                    @elseif($key === 'attivi')
                                        <a class="btn btn-sm btn-success" href="{{ action([$controller,'checkOutPage'], ['code'=>$item->code]) }}">Check-out</a>
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
